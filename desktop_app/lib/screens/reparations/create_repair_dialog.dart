import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart'; // For kIsWeb
import 'dart:async';
import 'dart:io';
import 'package:image_picker/image_picker.dart';
import 'dart:ui' as ui;
import 'package:path_provider/path_provider.dart';
import 'package:flutter/services.dart'; // For capturing platform exceptions
import 'package:camera/camera.dart';
import '../../widgets/camera_preview_modal.dart';
import '../../services/api_service.dart';
import '../../theme/macos_theme.dart';
import '../../widgets/new_client_modal.dart';

// Task blue color scheme
const Color _repairBlue = Color(0xFF007AFF);
const Color _repairBlueLight = Color(0xFF5AC8FA);

class CreateRepairDialog extends StatefulWidget {
  final ApiService apiService;

  const CreateRepairDialog({Key? key, required this.apiService}) : super(key: key);

  @override
  _CreateRepairDialogState createState() => _CreateRepairDialogState();
}

class _CreateRepairDialogState extends State<CreateRepairDialog> {
  final _formKey = GlobalKey<FormState>();
  final PageController _pageController = PageController();
  int _currentStep = 0;
  
  // -- FIELDS --

  // Step 1: Client
  final TextEditingController _clientSearchController = TextEditingController();
  Map<String, dynamic>? _selectedClient;
  List<Map<String, dynamic>> _clientSearchResults = [];
  bool _isSearchingClients = false;
  Timer? _debounce;
  bool _showClientDropdown = false;

  // Step 2: Device & Photo
  String _typeAppareil = 'Smartphone';
  final TextEditingController _marqueController = TextEditingController();
  final TextEditingController _modeleController = TextEditingController();
  final TextEditingController _problemeController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  
  // Photo
  XFile? _capturedImage;
  final ImagePicker _picker = ImagePicker();

  // Step 3: Details & Command
  final TextEditingController _prixController = TextEditingController();
  final TextEditingController _notesController = TextEditingController();
  
  bool _createCommand = false;
  String? _selectedSupplierId;
  final TextEditingController _partNameController = TextEditingController();
  final TextEditingController _partPriceController = TextEditingController();
  final TextEditingController _partQuantityController = TextEditingController(text: '1');
  List<dynamic> _suppliers = [];
  List<dynamic> _employees = [];
  bool _isLoadingDependencies = false;

  int? _selectedTechnicianId;

  String _statut = 'nouvelle_intervention';
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    _loadDependencies();
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _pageController.dispose();
    _clientSearchController.dispose();
    _marqueController.dispose();
    _modeleController.dispose();
    _problemeController.dispose();
    _passwordController.dispose();
    _prixController.dispose();
    _notesController.dispose();
    _partNameController.dispose();
    _partPriceController.dispose();
    _partQuantityController.dispose();
    super.dispose();
  }

  Future<void> _loadDependencies() async {
    setState(() => _isLoadingDependencies = true);
    try {
      final suppliersFuture = widget.apiService.getSuppliers();
      final employeesFuture = widget.apiService.getEmployees();
      
      final results = await Future.wait([suppliersFuture, employeesFuture]);
      
      if (mounted) {
        setState(() {
          _suppliers = results[0] as List<dynamic>;
          _employees = results[1] as List<dynamic>;
          _isLoadingDependencies = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoadingDependencies = false);
      }
    }
  }

  // --- Logic ---

  Future<void> _takePhoto() async {
    // Universal implementation using CameraPreviewModal
    if (mounted) {
      final XFile? result = await showDialog<XFile>(
        context: context,
        builder: (ctx) => const CameraPreviewModal(),
      );

      if (result != null) {
        setState(() {
          _capturedImage = result;
        });
      }
    }
  }


  Future<void> _pickPhoto() async {
    try {
      final XFile? photo = await _picker.pickImage(source: ImageSource.gallery);
      if (photo != null) {
        setState(() {
          _capturedImage = photo;
        });
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur galerie: $e')));
    }
  }

  void _onClientSearchChanged(String query) {
    String clean = query.replaceAll(RegExp(r'[^0-9]'), '');
    
    // Auto-format: 0X -> 33X
    if (clean.startsWith('0')) {
      clean = '33${clean.substring(1)}';
      
      // Update text field without breaking cursor (put at end)
      _clientSearchController.value = TextEditingValue(
        text: clean,
        selection: TextSelection.collapsed(offset: clean.length),
      );
    } else if (query != clean) {
       // Enforce numbers only if non-numbers were typed
       _clientSearchController.value = TextEditingValue(
        text: clean,
        selection: TextSelection.collapsed(offset: clean.length),
      );
    }

    if (_debounce?.isActive ?? false) _debounce!.cancel();
    
    _debounce = Timer(const Duration(milliseconds: 300), () {
      if (clean.length >= 2) {
        _searchClients(clean);
      } else {
        setState(() {
          _clientSearchResults = [];
          _showClientDropdown = false;
        });
      }
    });
  }

  Future<void> _searchClients(String query) async {
    setState(() => _isSearchingClients = true);
    try {
      final response = await widget.apiService.getClients(search: query, limit: 10);
      final clientsData = response['clients'] as List<dynamic>? ?? [];
      
      if (mounted) {
        setState(() {
          _clientSearchResults = clientsData.map((c) => Map<String, dynamic>.from(c)).toList();
          _isSearchingClients = false;
          _showClientDropdown = true;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _isSearchingClients = false;
          _clientSearchResults = [];
        });
      }
    }
  }

  void _selectClient(Map<String, dynamic> client) {
    setState(() {
      _selectedClient = client;
      _clientSearchController.text = "${client['nom']} ${client['prenom'] ?? ''}".trim();
      _showClientDropdown = false;
    });
  }

  void _showNewClientDialog() async {
    final result = await showDialog<Map<String, dynamic>>(
      context: context,
      builder: (ctx) => NewClientModal(
        apiService: widget.apiService,
        initialPhone: _clientSearchController.text, // Pass current input
      ),
    );
    
    if (result != null && mounted) {
      _selectClient(result);
    }
  }

  void _nextStep() {
    if (_currentStep == 0) {
      if (_selectedClient == null) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Veuillez sélectionner un client')));
        return;
      }
    } else if (_currentStep == 1) {
      // Validate device fields
      if (_modeleController.text.isEmpty) {
         ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Veuillez entrer le modèle')));
         return;
      }

      // Mandatory Photo Check
      if (_capturedImage == null) {
         ScaffoldMessenger.of(context).showSnackBar(
           const SnackBar(
             content: Text('⚠️ Photo obligatoire'),
             backgroundColor: Colors.red,
             behavior: SnackBarBehavior.floating,
           )
         );
         return;
      }
    }

    if (_currentStep < 2) {
      setState(() => _currentStep++);
      _pageController.nextPage(duration: const Duration(milliseconds: 300), curve: Curves.easeInOut);
    } else {
      _submit();
    }
  }

  void _prevStep() {
    if (_currentStep > 0) {
      setState(() => _currentStep--);
      _pageController.previousPage(duration: const Duration(milliseconds: 300), curve: Curves.easeInOut);
    }
  }

  Future<void> _submit() async {
      if (_createCommand && _selectedSupplierId == null) {
         ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Veuillez sélectionner un fournisseur pour la commande')));
         return;
      }
      
      if (_problemeController.text.isEmpty) {
         ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Veuillez décrire le problème')));
         return;
      }

      setState(() => _isSubmitting = true);

      try {
        final data = {
          'client_id': _selectedClient!['id'],
          'type_appareil': _typeAppareil,
          'marque': _marqueController.text,
          'modele': _modeleController.text,
          'description_probleme': _problemeController.text,
          'mot_de_passe': _passwordController.text,
          'prix_reparation': _prixController.text.isNotEmpty ? double.tryParse(_prixController.text.replaceAll(',', '.')) : null,
          'statut': _statut,
          'notes_techniques': _notesController.text,
          'commande_requise': _createCommand,
        };

        if (_selectedTechnicianId != null) {
          data['technicien_id'] = _selectedTechnicianId;
        }

        if (_createCommand) {
          data['commande'] = {
            'fournisseur_id': _selectedSupplierId,
            'nom_piece': _partNameController.text,
            'prix_estime': _partPriceController.text.isNotEmpty ? double.tryParse(_partPriceController.text.replaceAll(',', '.')) : null,
            'quantite': int.tryParse(_partQuantityController.text) ?? 1,
          };
        }

        List<int>? imageBytes;
        String? imagePath;

        if (kIsWeb) {
           imageBytes = await _capturedImage?.readAsBytes();
        } else {
           imagePath = _capturedImage?.path;
        }

        await widget.apiService.createReparation(data, imagePath: imagePath, imageBytes: imageBytes);

        if (mounted) {
          Navigator.of(context).pop(true);
        }
      } catch (e) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red),
          );
        }
      } finally {
        if (mounted) setState(() => _isSubmitting = false);
      }
  }

  double _getDialogHeight() {
    if (_currentStep == 0) {
      if (_showClientDropdown && _clientSearchResults.isNotEmpty) {
         return 800;
      }
      if (_selectedClient != null) {
         return 500;
      }
      // If we are showing "No client found" box, we need more space than 340
      if (_clientSearchController.text.isNotEmpty && _clientSearchResults.isEmpty) {
        return 450;
      }
      return 340; // Default when just searching or empty
    }
    return 800; // Steps 2 & 3 need room for scrolling
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final bgColor = isDark ? const Color(0xFF1E1E1E) : Colors.white;
    final textColor = isDark ? Colors.white : Colors.black87;

    return Dialog(
       shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
       child: AnimatedContainer(
         duration: const Duration(milliseconds: 300),
         curve: Curves.easeInOut,
         width: 700,
         height: _getDialogHeight(), 
         decoration: BoxDecoration(
           color: bgColor,
           borderRadius: BorderRadius.circular(20),
         ),
         child: Column(
           children: [
             // Header with Steps
             Container(
               padding: const EdgeInsets.all(20),
               decoration: BoxDecoration(
                 gradient: LinearGradient(
                   colors: [_repairBlue, _repairBlueLight],
                   begin: Alignment.topLeft,
                   end: Alignment.bottomRight,
                 ),
                 borderRadius: const BorderRadius.only(
                   topLeft: Radius.circular(20),
                   topRight: Radius.circular(20),
                 ),
               ),
               child: Row(
                 children: [
                   Container(
                     padding: const EdgeInsets.all(10),
                     decoration: BoxDecoration(
                       color: Colors.white.withOpacity(0.2),
                       shape: BoxShape.circle,
                     ),
                     child: Text(
                       "${_currentStep + 1}/3",
                       style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.white),
                     ),
                   ),
                   const SizedBox(width: 16),
                   Expanded(
                     child: Column(
                       crossAxisAlignment: CrossAxisAlignment.start,
                       children: [
                         const Text(
                           'Nouvelle Réparation',
                           style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.white),
                         ),
                         Text(
                           _stepTitle(_currentStep),
                           style: const TextStyle(fontSize: 13, color: Colors.white70),
                         ),
                       ],
                     ),
                   ),
                   IconButton(
                     onPressed: () => Navigator.pop(context),
                     icon: const Icon(Icons.close, color: Colors.white),
                   ),
                 ],
               ),
             ),

             // Page View
             Expanded(
               child: PageView(
                 controller: _pageController,
                 physics: const NeverScrollableScrollPhysics(), // Disable swipe
                 children: [
                   _buildStep1Client(isDark, textColor, bgColor),
                   _buildStep2Device(isDark, textColor, bgColor),
                   _buildStep3Details(isDark, textColor, bgColor),
                 ],
               ),
             ),

             // Footer Navigation
             Container(
               padding: const EdgeInsets.all(20),
               decoration: BoxDecoration(
                 border: Border(top: BorderSide(color: Theme.of(context).dividerColor.withOpacity(0.3))),
               ),
               child: Row(
                 mainAxisAlignment: MainAxisAlignment.spaceBetween,
                 children: [
                   if (_currentStep > 0)
                     TextButton.icon(
                       onPressed: _prevStep,
                       icon: const Icon(Icons.arrow_back),
                       label: const Text("Précédent"),
                     )
                   else
                     const SizedBox.shrink(),

                   ElevatedButton.icon(
                     onPressed: _isSubmitting ? null : _nextStep,
                     style: ElevatedButton.styleFrom(
                       backgroundColor: _repairBlue,
                       foregroundColor: Colors.white,
                       padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
                       shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                     ),
                     icon: _isSubmitting 
                         ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                         : Icon(_currentStep == 2 ? Icons.check : Icons.arrow_forward),
                     label: Text(_currentStep == 2 ? "Créer la réparation" : "Suivant"),
                   ),
                 ],
               ),
             ),
           ],
         ),
       ),
    );
  }

  String _stepTitle(int step) {
    switch (step) {
      case 0: return "Sélection du client";
      case 1: return "Informations de l'appareil";
      case 2: return "Détails et Options";
      default: return "";
    }
  }

  // -- Step Widgets --

  Widget _buildStep1Client(bool isDark, Color textColor, Color bgColor) {
    return Padding(
      padding: const EdgeInsets.all(32),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
           Text("Saisissez le numéro de téléphone pour rechercher ou ajouter un client", style: TextStyle(color: textColor.withOpacity(0.7), fontSize: 13)),
           const SizedBox(height: 12),
           TextFormField(
             controller: _clientSearchController,
             autofocus: true,
             style: TextStyle(color: textColor, fontSize: 16),
             keyboardType: TextInputType.phone,
             decoration: _buildInputDecoration(
               hint: 'Numéro de téléphone (ex: 06...)',
               icon: Icons.phone,
               isDark: isDark,
               suffixIcon: _isSearchingClients 
                   ? const Padding(padding: EdgeInsets.all(12), child: SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2)))
                   : _selectedClient != null 
                     ? IconButton(
                          icon: Icon(Icons.close, color: textColor.withOpacity(0.5)),
                          onPressed: () {
                            setState(() {
                              _selectedClient = null;
                              _clientSearchController.clear();
                            });
                          },
                       )
                     : null,
             ),
             onChanged: _onClientSearchChanged,
           ),

          const SizedBox(height: 20),
          
          if (_selectedClient == null && _clientSearchController.text.isNotEmpty && _clientSearchResults.isEmpty && !_isSearchingClients)
             Container(
                width: double.infinity,
                margin: const EdgeInsets.only(top: 16),
                padding: const EdgeInsets.symmetric(vertical: 32, horizontal: 24),
                decoration: BoxDecoration(
                  color: isDark ? Colors.white.withOpacity(0.05) : Colors.grey.withOpacity(0.05),
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: Theme.of(context).dividerColor.withOpacity(0.3), style: BorderStyle.none), // Removed border for cleaner look or keep it subtle
                ),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    // Icon and Text removed per user request to fix layout overflow
                    SizedBox(
                      width: 200,
                      child: ElevatedButton.icon(
                        onPressed: _showNewClientDialog,
                        icon: const Icon(Icons.add),
                        label: const Text("Nouveau client"),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: _repairBlue,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 16),
                          elevation: 2,
                        ),
                      ),
                    ),
                  ],
                ),
             )
          else if (_showClientDropdown && _clientSearchResults.isNotEmpty)
            Expanded(
              child: Container(
                decoration: BoxDecoration(
                  color: isDark ? Colors.white.withOpacity(0.05) : Colors.grey.withOpacity(0.05),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: Theme.of(context).dividerColor.withOpacity(0.3)),
                ),
                child: Column(
                  children: [
                    Expanded(
                      child: ListView.separated(
                        itemCount: _clientSearchResults.length,
                        separatorBuilder: (c, i) => Divider(height: 1, color: Theme.of(context).dividerColor.withOpacity(0.1)),
                        itemBuilder: (ctx, i) {
                          final client = _clientSearchResults[i];
                          return ListTile(
                            dense: true,
                            leading: CircleAvatar(
                              radius: 16,
                              backgroundColor: _repairBlue.withOpacity(0.1),
                              child: Text((client['nom']?.toString().substring(0, 1) ?? '?').toUpperCase(), style: TextStyle(color: _repairBlue, fontWeight: FontWeight.bold, fontSize: 12)),
                            ),
                            title: Text("${client['nom']} ${client['prenom'] ?? ''}".trim(), style: const TextStyle(fontWeight: FontWeight.w600)),
                            subtitle: Text(client['telephone'] ?? 'Sans téléphone'),
                            onTap: () => _selectClient(client),
                          );
                        },
                      ),
                    ),
                    const Divider(height: 1),
                    InkWell(
                       onTap: _showNewClientDialog,
                       child: Container(
                         width: double.infinity,
                         padding: const EdgeInsets.all(12),
                         color: _repairBlue.withOpacity(0.1),
                         child: Row(
                           mainAxisAlignment: MainAxisAlignment.center,
                           children: [
                             Icon(Icons.add_circle, size: 16, color: _repairBlue),
                             const SizedBox(width: 8),
                             Text("Créer un nouveau client", style: TextStyle(color: _repairBlue, fontWeight: FontWeight.bold)),
                           ],
                         ),
                       ),
                    )
                  ],
                ),
              ),
            ),
            
          if (_selectedClient != null && !_showClientDropdown)
             Expanded(
               child: Center(
                 child: Column(
                   mainAxisAlignment: MainAxisAlignment.center,
                   children: [
                     Icon(Icons.check_circle, size: 60, color: _repairBlue),
                     const SizedBox(height: 16),
                     Text("${_selectedClient!['nom']} ${_selectedClient!['prenom'] ?? ''}", style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: textColor)),
                     const SizedBox(height: 8),
                     Text(_selectedClient!['telephone'] ?? '', style: TextStyle(color: textColor.withOpacity(0.6))),
                     const SizedBox(height: 24),
                     OutlinedButton.icon(
                       onPressed: () {
                         setState(() {
                           _selectedClient = null;
                           _clientSearchController.clear();
                         });
                       },
                       icon: const Icon(Icons.refresh),
                       label: const Text("Changer de client"),
                     )
                   ],
                 ),
               ),
             ),
        ],
      ),
    );
  }

  Widget _buildStep2Device(bool isDark, Color textColor, Color bgColor) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(32),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
           // Type & Marque row
           Row(
             children: [
                Expanded(
                  flex: 1,
                  child: Container(
                    decoration: BoxDecoration(
                      color: isDark ? Colors.white.withOpacity(0.05) : Colors.grey.withOpacity(0.05),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: Theme.of(context).dividerColor.withOpacity(0.3)),
                    ),
                    padding: const EdgeInsets.only(left: 12, right: 4),
                    child: DropdownButtonHideUnderline(
                      child: DropdownButton<String>(
                        value: _typeAppareil,
                        dropdownColor: bgColor,
                        isExpanded: true,
                        style: TextStyle(color: textColor),
                        items: ['Smartphone', 'Tablette', 'Ordinateur Portable', 'Tour PC', 'Console', 'Autre']
                            .map((t) => DropdownMenuItem(value: t, child: Text(t))).toList(),
                        onChanged: (val) => setState(() => _typeAppareil = val!),
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  flex: 2,
                  child: TextFormField(
                    controller: _marqueController,
                    style: TextStyle(color: textColor),
                    decoration: _buildInputDecoration(hint: 'Marque (ex: Apple)', icon: Icons.branding_watermark, isDark: isDark),
                  ),
                ),
             ],
           ),
           const SizedBox(height: 16),
           
           // Modele
           TextFormField(
             controller: _modeleController,
             style: TextStyle(color: textColor),
             decoration: _buildInputDecoration(hint: 'Modèle (ex: iPhone 13 Pro)', icon: Icons.smartphone, isDark: isDark),
           ),
           
           const SizedBox(height: 16),
           
           // Password
           TextFormField(
             controller: _passwordController,
             style: TextStyle(color: textColor),
             decoration: _buildInputDecoration(hint: 'Mot de passe / Code de verrouillage', icon: Icons.lock, isDark: isDark),
           ),
           
           const SizedBox(height: 24),

           // Photo Capture Section (Mandatory)
           Text("Photo de l'appareil (Obligatoire)", style: TextStyle(color: textColor, fontWeight: FontWeight.bold)),
           const SizedBox(height: 8),
           GestureDetector(
             onTap: _takePhoto,
             child: Container(
               height: 220,
               width: double.infinity,
               decoration: BoxDecoration(
                 color: isDark ? Colors.white.withOpacity(0.05) : Colors.grey.withOpacity(0.1),
                 borderRadius: BorderRadius.circular(16),
                 border: Border.all(
                   color: _capturedImage == null ? Theme.of(context).dividerColor.withOpacity(0.2) : _repairBlue,
                   width: _capturedImage == null ? 1 : 2
                 ),
               ),
               child: ClipRRect(
                 borderRadius: BorderRadius.circular(14),
                 child: _capturedImage != null
                   ? Stack(
                       fit: StackFit.expand,
                       children: [
                         kIsWeb 
                           ? Image.network(_capturedImage!.path, fit: BoxFit.cover)
                           : Image.file(File(_capturedImage!.path), fit: BoxFit.cover),
                         
                         // Remove button (GestureDetector consumes tap, so using IconButton inside Stack might need handling, but usually generic onTap on parent works if child doesn't block. IconButton DOES block touches. So need to ensure delete works.)
                         Positioned(
                           top: 8,
                           right: 8,
                           child: IconButton(
                             onPressed: () => setState(() => _capturedImage = null),
                             icon: const Icon(Icons.delete, color: Colors.white),
                             style: IconButton.styleFrom(backgroundColor: Colors.black54),
                             tooltip: "Supprimer la photo",
                           ),
                         ),
                       ],
                     )
                   : Column(
                       mainAxisAlignment: MainAxisAlignment.center,
                       children: [
                         Icon(Icons.add_a_photo_outlined, size: 64, color: _repairBlue.withOpacity(0.5)),
                         const SizedBox(height: 16),
                         Text(
                           "Aucune photo sélectionnée",
                           style: TextStyle(color: textColor.withOpacity(0.5), fontWeight: FontWeight.w500, fontSize: 16),
                         ),
                         const SizedBox(height: 8),
                         Text(
                           "Une photo est obligatoire pour continuer",
                           style: TextStyle(color: textColor.withOpacity(0.3), fontSize: 12),
                         ),
                       ],
                     ),
               ),
             ),
           ),
           
           const SizedBox(height: 16),
           
           // Action Buttons
           Row(
             children: [
               Expanded(
                 child: ElevatedButton.icon(
                   onPressed: _takePhoto,
                   icon: const Icon(Icons.camera_alt),
                   label: const Text("Prendre une photo"),
                   style: ElevatedButton.styleFrom(
                     backgroundColor: _repairBlue,
                     foregroundColor: Colors.white,
                     padding: const EdgeInsets.symmetric(vertical: 18),
                     shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                     elevation: 0,
                   ),
                 ),
               ),
               const SizedBox(width: 12),
               Expanded(
                 child: OutlinedButton.icon(
                   onPressed: _pickPhoto,
                   icon: const Icon(Icons.photo_library),
                   label: const Text("Galerie"),
                   style: OutlinedButton.styleFrom(
                     padding: const EdgeInsets.symmetric(vertical: 18),
                     shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                     side: BorderSide(color: isDark ? Colors.white24 : Colors.grey.shade300),
                     foregroundColor: textColor,
                   ),
                 ),
               ),
             ],
           ),

           const SizedBox(height: 12),
        ],
      ),
    );
  }

  Widget _buildStep3Details(bool isDark, Color textColor, Color bgColor) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(32),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
           // Problème rencontré (Moved from Step 2)
           const Text("Problème rencontré", style: TextStyle(fontWeight: FontWeight.bold)),
           const SizedBox(height: 8),
           
           TextFormField(
             controller: _problemeController,
             style: TextStyle(color: textColor),
             minLines: 3,
             maxLines: 5,
             decoration: _buildInputDecoration(hint: 'Décrivez la panne (écran cassé, batterie HS, etc.)', icon: Icons.report_problem, isDark: isDark, alignIconTop: true),
           ),
           
           const SizedBox(height: 24),

           // Prix et Notes
           Row(
             children: [
               Expanded(
                 child: TextFormField(
                   controller: _prixController,
                   style: TextStyle(color: textColor),
                   keyboardType: TextInputType.number,
                   decoration: _buildInputDecoration(hint: 'Prix Est. (€)', icon: Icons.euro, isDark: isDark),
                 ),
               ),
               const SizedBox(width: 16),
               Expanded(
                 flex: 2,
                 child: TextFormField(
                   controller: _notesController,
                   style: TextStyle(color: textColor),
                   decoration: _buildInputDecoration(hint: 'Notes internes (optionnel)', icon: Icons.note, isDark: isDark),
                 ),
               ),
             ],
           ),
           
           const SizedBox(height: 32),
           
           // Command Toggle
           InkWell(
             onTap: () => setState(() => _createCommand = !_createCommand),
             borderRadius: BorderRadius.circular(12),
             child: Container(
               padding: const EdgeInsets.all(16),
               decoration: BoxDecoration(
                 color: _createCommand ? _repairBlue.withOpacity(0.1) : (isDark ? Colors.white.withOpacity(0.05) : Colors.grey.withOpacity(0.05)),
                 borderRadius: BorderRadius.circular(12),
                 border: Border.all(color: _createCommand ? _repairBlue : Colors.transparent),
               ),
               child: Row(
                 children: [
                   Icon(_createCommand ? Icons.check_box : Icons.check_box_outline_blank, color: _createCommand ? _repairBlue : Colors.grey),
                   const SizedBox(width: 12),
                   Expanded(
                     child: Column(
                       crossAxisAlignment: CrossAxisAlignment.start,
                       children: [
                         Text("Commander une pièce", style: TextStyle(color: textColor, fontWeight: FontWeight.bold)),
                         Text("Créer automatiquement une commande liée", style: TextStyle(color: textColor.withOpacity(0.6), fontSize: 12)),
                       ],
                     ),
                   ),
                 ],
               ),
             ),
           ),

           if (_createCommand) ...[
             const SizedBox(height: 16),
             Container(
               padding: const EdgeInsets.all(20),
               decoration: BoxDecoration(
                 color: isDark ? Colors.black26 : Colors.grey.shade100,
                 borderRadius: BorderRadius.circular(12),
                 border: Border.all(color: _repairBlue.withOpacity(0.3)),
               ),
               child: Column(
                 children: [
                   _isLoadingDependencies
                       ? const LinearProgressIndicator()
                       : Container(
                           decoration: BoxDecoration(
                             color: isDark ? Colors.white.withOpacity(0.05) : Colors.white,
                             borderRadius: BorderRadius.circular(12),
                             border: Border.all(color: Theme.of(context).dividerColor.withOpacity(0.3)),
                           ),
                           padding: const EdgeInsets.symmetric(horizontal: 12),
                           child: DropdownButtonHideUnderline(
                             child: DropdownButton<String>(
                               value: _selectedSupplierId,
                               hint: Text("Choisir un fournisseur", style: TextStyle(color: textColor.withOpacity(0.5))),
                               dropdownColor: bgColor,
                               isExpanded: true,
                               style: TextStyle(color: textColor),
                               items: _suppliers.map((s) => DropdownMenuItem(
                                 value: s['id'].toString(),
                                 child: Text(s['nom'] ?? s['nom_societe'] ?? 'Inconnu'),
                               )).toList(),
                               onChanged: (val) => setState(() => _selectedSupplierId = val),
                             ),
                           ),
                         ),
                   const SizedBox(height: 12),
                   TextFormField(
                     controller: _partNameController,
                     style: TextStyle(color: textColor),
                     decoration: _buildInputDecoration(hint: 'Nom de la pièce', icon: Icons.extension, isDark: isDark),
                   ),
                   const SizedBox(height: 12),
                   Row(
                     children: [
                       Expanded(
                         child: TextFormField(
                           controller: _partPriceController,
                           style: TextStyle(color: textColor),
                           keyboardType: TextInputType.number,
                           decoration: _buildInputDecoration(hint: 'Prix pièce (€)', icon: Icons.euro, isDark: isDark),
                         ),
                       ),
                       const SizedBox(width: 12),
                       Expanded(
                         child: TextFormField(
                           controller: _partQuantityController,
                           style: TextStyle(color: textColor),
                           keyboardType: TextInputType.number,
                           decoration: _buildInputDecoration(hint: 'Qté', icon: Icons.numbers, isDark: isDark),
                         ),
                       ),
                     ],
                   ),
                 ],),),],
           
           const SizedBox(height: 24),

           // Technician Assignment
           Text("Attribuer à un technicien", style: TextStyle(color: textColor, fontWeight: FontWeight.bold)),
           const SizedBox(height: 8),
           Container(
             decoration: BoxDecoration(
               color: isDark ? Colors.white.withOpacity(0.05) : Colors.grey.withOpacity(0.05),
               borderRadius: BorderRadius.circular(12),
               border: Border.all(color: Theme.of(context).dividerColor.withOpacity(0.3)),
             ),
             padding: const EdgeInsets.symmetric(horizontal: 12),
             child: DropdownButtonHideUnderline(
               child: DropdownButton<int>(
                 value: _selectedTechnicianId,
                 hint: Text("Sélectionner un technicien", style: TextStyle(color: textColor.withOpacity(0.5))),
                 dropdownColor: bgColor,
                 isExpanded: true,
                 style: TextStyle(color: textColor),
                 items: _employees.map((e) => DropdownMenuItem<int>(
                   value: int.tryParse(e['id'].toString()),
                   child: Row(
                     children: [
                       CircleAvatar(
                         radius: 12,
                         backgroundColor: _repairBlue.withOpacity(0.2),
                         child: Text((e['full_name']?.toString().substring(0, 1) ?? '?').toUpperCase(), style: TextStyle(fontSize: 10, color: _repairBlue)),
                       ),
                       const SizedBox(width: 8),
                       Text(e['full_name'] ?? 'Inconnu'),
                     ],
                   ),
                 )).toList(),
                 onChanged: (val) => setState(() => _selectedTechnicianId = val),
               ),
             ),
           ),
        ],
      ),
    );
  }

  InputDecoration _buildInputDecoration({
    required String hint,
    required IconData icon,
    required bool isDark,
    bool alignIconTop = false,
    Widget? suffixIcon,
  }) {
    final cardColor = isDark ? Colors.white.withOpacity(0.05) : Colors.grey.withOpacity(0.05);
    return InputDecoration(
      hintText: hint,
      hintStyle: TextStyle(color: isDark ? Colors.white38 : Colors.black38),
      prefixIcon: Padding(
        padding: EdgeInsets.only(top: alignIconTop ? 12 : 0),
        child: Icon(icon, color: _repairBlue, size: 20),
      ),
      suffixIcon: suffixIcon,
      filled: true,
      fillColor: cardColor,
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide.none,
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: Theme.of(context).dividerColor.withOpacity(0.3)),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: _repairBlue, width: 2),
      ),
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
    );
  }
}


