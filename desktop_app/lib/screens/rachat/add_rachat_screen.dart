import 'dart:async';
import 'dart:io';
import 'dart:typed_data';
import 'package:flutter/foundation.dart'; // For kIsWeb
import 'package:flutter/material.dart';
import 'package:flutter/cupertino.dart';
import 'package:provider/provider.dart';
import 'package:image_picker/image_picker.dart';
import 'package:camera/camera.dart';
import 'package:signature/signature.dart';
import '../../services/api_service.dart';
import '../../services/auth_service.dart';
import '../../theme/macos_theme.dart';
import '../../widgets/new_client_modal.dart';
import 'package:http/http.dart' as http;
import 'package:path_provider/path_provider.dart';
import '../../config/api_config.dart';

class AddRachatScreen extends StatefulWidget {
  const AddRachatScreen({super.key});

  @override
  State<AddRachatScreen> createState() => _AddRachatScreenState();
}

class _AddRachatScreenState extends State<AddRachatScreen> {
  final PageController _pageController = PageController();
  int _currentStep = 0;
  bool _isSubmitting = false;

  // -- STEP 1: CLIENT --
  final TextEditingController _clientSearchController = TextEditingController();
  Timer? _debounce;
  bool _isSearchingClients = false;
  List<Map<String, dynamic>> _clientSearchResults = [];
  Map<String, dynamic>? _selectedClient;
  bool _showClientDropdown = false;

  // -- STEP 2: APPAREIL --
  String _typeAppareil = 'Smartphone';
  final TextEditingController _marqueController = TextEditingController();
  final TextEditingController _modeleController = TextEditingController();
  final TextEditingController _sinController = TextEditingController(); // IMEI / Série
  final TextEditingController _prixController = TextEditingController();
  bool _isFunctional = true;
  
  XFile? _photoIdentite;
  XFile? _photoAppareil;
  
  final ImagePicker _picker = ImagePicker();

  // -- STEP 3: SIGNATURE & CLIENT PHOTO --
  final SignatureController _signatureController = SignatureController(
    penStrokeWidth: 3,
    penColor: Colors.black,
    exportBackgroundColor: Colors.white,
  );
  
  CameraController? _cameraController;
  XFile? _photoClient;
  bool _isCameraInitialized = false;

  ApiService get _apiService => context.read<AuthService>().getApiService();

  // -- REMOTE SCREEN VARS --
  bool _useRemoteScreen = false;
  List<Map<String, dynamic>> _availableScreens = [];
  String? _selectedScreenToken;
  String? _selectedScreenName;
  bool _isWaitingForRemoteSignature = false;
  Uint8List? _remoteSignatureBytes; // Stocke la signature reçue
  Timer? _remotePollTimer;

  @override
  void initState() {
    super.initState();
    _initCamera();
    _loadAvailableScreens();
  }

  Future<void> _loadAvailableScreens() async {
     try {
       // Load screens and users in parallel
       final results = await Future.wait([
         _apiService.getScreens(),
         _apiService.getShopUsers(),
       ]);
       
       final screens = results[0] as List<Map<String, dynamic>>;
       final users = results[1] as List<Map<String, dynamic>>;
       
       final currentUserId = context.read<AuthService>().currentUser?.id;
       int? assignedScreenId;
       
       // Find my assignment
       if (currentUserId != null) {
         try {
           final me = users.firstWhere((u) => u['id'] == currentUserId);
           assignedScreenId = me['assigned_screen_id'];
         } catch (_) {}
       }
       
       if (mounted) {
         setState(() {
           _availableScreens = screens;
           
           if (screens.isNotEmpty) {
             // Default to assigned screen if found
             if (assignedScreenId != null) {
                try {
                  final assignedScreen = screens.firstWhere((s) => s['id'] == assignedScreenId);
                  _selectedScreenToken = assignedScreen['token'];
                  _selectedScreenName = assignedScreen['name'];
                } catch (_) {
                  // Assigned screen not found in list (maybe deleted?) -> Fallback to first
                  _selectedScreenToken = screens.first['token'];
                  _selectedScreenName = screens.first['name'];
                }
             } else {
               // No assignment -> Fallback to first
               _selectedScreenToken = screens.first['token'];
               _selectedScreenName = screens.first['name'];
             }
           }
         });
       }
     } catch (e) {
       print("Erreur chargement écrans: $e");
     }
  }

  Future<void> _initCamera() async {
    try {
      final cameras = await availableCameras();
      if (cameras.isEmpty) return;
      
      // Chercher une caméra frontale si possible, sinon la première
      final camera = cameras.firstWhere(
        (c) => c.lensDirection == CameraLensDirection.front,
        orElse: () => cameras.first,
      );
      
      _cameraController = CameraController(
        camera,
        ResolutionPreset.medium,
        enableAudio: false,
      );

      await _cameraController!.initialize();
      if (mounted) setState(() => _isCameraInitialized = true);
    } catch (e) {
      print('Erreur camera: $e');
    }
  }

  @override
  void dispose() {
    _pageController.dispose();
    _clientSearchController.dispose();
    _debounce?.cancel();
    _marqueController.dispose();
    _modeleController.dispose();
    _sinController.dispose();
    _prixController.dispose();
    _signatureController.dispose();
    _cameraController?.dispose();
    _remotePollTimer?.cancel();
    super.dispose();
  }

  // --- LOGIQUE CLIENT ---

  void _onClientSearchChanged(String query) {
    String clean = query.replaceAll(RegExp(r'[^0-9]'), '');
    if (clean.startsWith('0')) clean = '33${clean.substring(1)}';
    
    if (_clientSearchController.text != clean) {
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
      final response = await _apiService.getClients(search: query, limit: 10);
      final clientsData = response['clients'] as List<dynamic>? ?? [];
      
      if (mounted) {
        setState(() {
          _clientSearchResults = clientsData.map((c) => Map<String, dynamic>.from(c)).toList();
          _isSearchingClients = false;
          _showClientDropdown = true;
        });
      }
    } catch (e) {
      if (mounted) setState(() => _isSearchingClients = false);
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
        apiService: _apiService,
        initialPhone: _clientSearchController.text,
      ),
    );
    if (result != null && mounted) {
      _selectClient(result);
    }
  }

  // --- LOGIQUE PHOTOS ---

  Future<void> _pickPhoto(bool isIdentite) async {
    // Choix source
    final source = await showDialog<ImageSource>(
      context: context,
      builder: (ctx) => SimpleDialog(
        title: const Text("Source photo"),
        children: [
          SimpleDialogOption(onPressed: () => Navigator.pop(ctx, ImageSource.camera), child: const Text("Caméra")),
          SimpleDialogOption(onPressed: () => Navigator.pop(ctx, ImageSource.gallery), child: const Text("Galerie")),
        ],
      ),
    );
    
    if (source == null) return;

    try {
      final XFile? file = await _picker.pickImage(source: source, imageQuality: 80);
      if (file != null) {
        setState(() {
          if (isIdentite) _photoIdentite = file;
          else _photoAppareil = file;
        });
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur photo: $e')));
    }
  }

  Future<void> _captureClientPhoto() async {
    if (_cameraController == null || !_cameraController!.value.isInitialized) return;
    
    try {
      final XFile file = await _cameraController!.takePicture();
      setState(() => _photoClient = file);
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur capture: $e')));
    }
  }

  // --- NAVIGATION ---

  void _nextStep() {
    if (_currentStep == 0 && _selectedClient == null) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Veuillez sélectionner un client')));
      return;
    }
    if (_currentStep == 1) {
      if (_marqueController.text.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Marque obligatoire')));
        return;
      }
      if (_modeleController.text.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Modèle obligatoire')));
        return;
      }
      if (_photoIdentite == null) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Photo pièce d\'identité obligatoire')));
        return;
      }
      if (_photoAppareil == null) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Photo appareil obligatoire')));
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
    if (_signatureController.isEmpty && _remoteSignatureBytes == null) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Signature du client obligatoire')));
      return;
    }
    
    // Si pas de photo client, on peut bloquer ou avertir. L'utilisateur a demandé "Photo du client LORS de la signature".
    // On peut forcer la capture si elle n'est pas faite.
    if (_photoClient == null) {
       // Tentative auto capture ?
       if (_isCameraInitialized) {
         await _captureClientPhoto();
       }
       if (_photoClient == null) {
         ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Photo du client obligatoire')));
         return;
       }
    }

    setState(() => _isSubmitting = true);

    try {
      final signatureBytes = _remoteSignatureBytes ?? await _signatureController.toPngBytes();
      if (signatureBytes == null) throw Exception("Erreur récupération signature");

      final data = {
        'client_id': _selectedClient!['id'],
// ... rest truncated in thought but handled by logic ...
        'type_appareil': _typeAppareil,
        'marque': _marqueController.text,
        'modele': _modeleController.text,
        'sin': _sinController.text,
        'prix': _prixController.text.replaceAll(',', '.'),
        'fonctionnel': _isFunctional ? '1' : '0',
      };

      final Map<String, dynamic> files = {
        'signature': signatureBytes, // Bytes
        'photo_identite': await _photoIdentite!.readAsBytes(),
        'photo_appareil': await _photoAppareil!.readAsBytes(),
        'photo_client': await _photoClient!.readAsBytes(), // _photoClient is set by download if remote
      };
      
      // Note: My API createRachat supports checks for String (path) or List<int> (bytes). 
      // XFile.readAsBytes returns Uint8List which is List<int>.

      await _apiService.createRachat(data, files);
      
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Rachat créé avec succès'), backgroundColor: Colors.green));
        Navigator.of(context).pop(true);
      }

    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red));
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  // --- UI BUILD ---

  @override
  Widget build(BuildContext context) {
    bool isDark = Theme.of(context).brightness == Brightness.dark;
    Color bgColor = isDark ? const Color(0xFF1E1E1E) : Colors.white;
    Color textColor = isDark ? Colors.white : Colors.black87;
    
    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 300),
        width: 800,
        height: 700,
        decoration: BoxDecoration(
          color: bgColor,
          borderRadius: BorderRadius.circular(20),
        ),
        child: Column(
          children: [
            // Header
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                gradient: const LinearGradient(colors: [Color(0xFF6366f1), Color(0xFF8b5cf6)]),
                borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
              ),
              child: Row(
                children: [
                  CircleAvatar(
                    backgroundColor: Colors.white24,
                    child: Text("${_currentStep + 1}", style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                  ),
                  const SizedBox(width: 16),
                  Text(
                    _getStepTitle(),
                    style: const TextStyle(fontSize: 18, color: Colors.white, fontWeight: FontWeight.bold),
                  ),
                  const Spacer(),
                  IconButton(onPressed: () => Navigator.pop(context), icon: const Icon(Icons.close, color: Colors.white))
                ],
              ),
            ),
            
            Expanded(
              child: PageView(
                  controller: _pageController,
                  physics: const NeverScrollableScrollPhysics(),
                  children: [
                    _buildStep1(isDark, textColor),
                    _buildStep2(isDark, textColor),
                    _buildStep3(isDark, textColor),
                  ],
              ),
            ),
            
            // Footer
            Padding(
              padding: const EdgeInsets.all(20),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  if (_currentStep > 0)
                    TextButton(onPressed: _prevStep, child: const Text("Précédent"))
                  else 
                    const SizedBox(),
                    
                  ElevatedButton(
                    onPressed: _isSubmitting ? null : _nextStep,
                    style: ElevatedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 16),
                      backgroundColor: const Color(0xFF6366f1),
                      foregroundColor: Colors.white
                    ),
                    child: _isSubmitting 
                      ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white))
                      : Text(_currentStep == 2 ? "Valider le rachat" : "Suivant"),
                  ),
                ],
              ),
            )
          ],
        ),
      ),
    );
  }

  String _getStepTitle() {
     switch(_currentStep) {
       case 0: return "Sélection du Client";
       case 1: return "Informations Appareil";
       case 2: return "Signature & Validation";
       default: return "";
     }
  }

  Widget _buildStep1(bool isDark, Color textColor) {
    return Padding(
      padding: const EdgeInsets.all(32),
      child: Column(
        children: [
          TextField(
            controller: _clientSearchController,
            style: TextStyle(color: textColor),
            decoration: InputDecoration(
              labelText: "Rechercher un client (Téléphone)",
              prefixIcon: const Icon(Icons.search),
              suffixIcon: _isSearchingClients 
                 ? const Padding(padding: EdgeInsets.all(12), child: CircularProgressIndicator(strokeWidth: 2)) 
                 : (_selectedClient != null ? IconButton(icon: const Icon(Icons.clear), onPressed: () {
                     setState(() { _selectedClient = null; _clientSearchController.clear(); });
                   }) : null),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
            ),
            onChanged: _onClientSearchChanged,
          ),
          
          if (_showClientDropdown && _clientSearchResults.isNotEmpty)
             Expanded(
               child: Container(
                 margin: const EdgeInsets.only(top: 10),
                 decoration: BoxDecoration(
                   border: Border.all(color: Colors.grey.withOpacity(0.3)),
                   borderRadius: BorderRadius.circular(12),
                 ),
                 child: ListView.separated(
                   itemCount: _clientSearchResults.length,
                   separatorBuilder: (_, __) => const Divider(height: 1),
                   itemBuilder: (context, index) {
                     final c = _clientSearchResults[index];
                     return ListTile(
                       title: Text("${c['nom']} ${c['prenom']}"),
                       subtitle: Text(c['telephone'] ?? ''),
                       onTap: () => _selectClient(c),
                     );
                   },
                 ),
               ),
             ),
             
          if (_selectedClient != null && !_showClientDropdown)
             Expanded(
               child: Center(
                 child: Column(
                   mainAxisAlignment: MainAxisAlignment.center,
                   children: [
                     const Icon(Icons.check_circle, size: 60, color: Colors.green),
                     const SizedBox(height: 16),
                     Text("${_selectedClient!['nom']} ${_selectedClient!['prenom']}", style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: textColor)),
                     Text(_selectedClient!['telephone'] ?? '', style: TextStyle(fontSize: 16, color: Colors.grey)),
                   ],
                 ),
               ),
             ),
             
          if (_clientSearchController.text.length > 2 && _clientSearchResults.isEmpty && !_isSearchingClients && _selectedClient == null)
             Expanded(
               child: Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Text("Aucun client trouvé"),
                      const SizedBox(height: 16),
                      ElevatedButton.icon(
                        onPressed: _showNewClientDialog,
                        icon: const Icon(Icons.add),
                        label: const Text("Créer un nouveau client"),
                      )
                    ],
                  )
               ),
             )
        ],
      ),
    );
  }

  Widget _buildStep2(bool isDark, Color textColor) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(32),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          DropdownButtonFormField<String>(
            value: _typeAppareil,
            dropdownColor: isDark ? const Color(0xFF2C2C2C) : Colors.white,
            style: TextStyle(color: textColor),
            decoration: const InputDecoration(labelText: "Type d'appareil"),
            items: ['Smartphone', 'Tablette', 'Ordinateur', 'Console', 'Autre']
              .map((e) => DropdownMenuItem(value: e, child: Text(e))).toList(),
            onChanged: (v) => setState(() => _typeAppareil = v!),
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(child: TextField(controller: _marqueController, style: TextStyle(color: textColor), decoration: const InputDecoration(labelText: "Marque"))),
              const SizedBox(width: 16),
              Expanded(child: TextField(controller: _modeleController, style: TextStyle(color: textColor), decoration: const InputDecoration(labelText: "Modèle"))),
            ],
          ),
          const SizedBox(height: 16),
          TextField(controller: _sinController, style: TextStyle(color: textColor), decoration: const InputDecoration(labelText: "N° Série / IMEI")),
          const SizedBox(height: 16),
          Row(
             children: [
               Expanded(child: TextField(controller: _prixController, keyboardType: TextInputType.number, style: TextStyle(color: textColor), decoration: const InputDecoration(labelText: "Prix Rachat (€)"))),
               const SizedBox(width: 16),
               Row(
                 children: [
                   Text("Fonctionnel ?", style: TextStyle(color: textColor)),
                   Switch(value: _isFunctional, onChanged: (v) => setState(() => _isFunctional = v)),
                 ],
               )
             ],
          ),
          const SizedBox(height: 24),
          const Divider(),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(child: _buildPhotoBox("Pièce d'identité", _photoIdentite, () => _pickPhoto(true), isDark, textColor)),
              const SizedBox(width: 16),
              Expanded(child: _buildPhotoBox("Photo Appareil", _photoAppareil, () => _pickPhoto(false), isDark, textColor)),
            ],
          )
        ],
      ),
    );
  }

  Widget _buildPhotoBox(String label, XFile? photo, VoidCallback onTap, bool isDark, Color textColor) {
    return InkWell(
      onTap: onTap,
      child: Container(
        height: 150,
        decoration: BoxDecoration(
          border: Border.all(color: Colors.grey),
          borderRadius: BorderRadius.circular(12),
          color: isDark ? Colors.black26 : Colors.grey[100],
        ),
        child: photo != null 
          ? ClipRRect(borderRadius: BorderRadius.circular(12), child: kIsWeb ? Image.network(photo.path, fit: BoxFit.cover) : Image.file(File(photo.path), fit: BoxFit.cover))
          : Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.add_a_photo, size: 32, color: Colors.grey),
                const SizedBox(height: 8),
                Text(label, style: TextStyle(color: textColor)),
              ],
            ),
      ),
    );
  }

  Widget _buildStep3(bool isDark, Color textColor) {
    // Si écran distant activé
    if (_useRemoteScreen) {
      if (_remoteSignatureBytes != null) {
        // Signature reçue !
        return Center(
          child: Column(
             mainAxisAlignment: MainAxisAlignment.center,
             children: [
               const Icon(Icons.check_circle, size: 64, color: Colors.green),
               const SizedBox(height: 16),
               Text("Signature et Photo Client reçues !", style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: textColor)),
               const SizedBox(height: 32),
               
               // Preview
               Row(
                 mainAxisAlignment: MainAxisAlignment.center,
                 children: [
                   Column(
                     children: [
                       const Text("Signature"),
                       const SizedBox(height: 8),
                       Container(
                         height: 150, width: 250,
                         decoration: BoxDecoration(border: Border.all(color: Colors.grey), color: Colors.white),
                         child: Image.memory(_remoteSignatureBytes!, fit: BoxFit.contain),
                       )
                     ],
                   ),
                   const SizedBox(width: 32),
                   if (_photoClient != null)
                     Column(
                       children: [
                         const Text("Photo Client"),
                         const SizedBox(height: 8),
                         Container(
                           height: 150, width: 250,
                           decoration: BoxDecoration(border: Border.all(color: Colors.grey), color: Colors.black),
                           child: kIsWeb ? Image.network(_photoClient!.path) : Image.file(File(_photoClient!.path)),
                         )
                       ],
                     ),
                 ],
               ),
               
               const SizedBox(height: 32),
               ElevatedButton.icon(
                 onPressed: () {
                   setState(() {
                     _remoteSignatureBytes = null;
                     _photoClient = null;
                     _isWaitingForRemoteSignature = false;
                   });
                 },
                 icon: const Icon(Icons.refresh),
                 label: const Text("Recommencer"),
               )
             ],
          ),
        );
      }
    
      if (_isWaitingForRemoteSignature) {
        return Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const CircularProgressIndicator(),
              const SizedBox(height: 24),
              Text("En attente de la signature sur $_selectedScreenName...", style: TextStyle(fontSize: 18, color: textColor)),
              const SizedBox(height: 8),
              const Text("Le client doit signer sur la tablette.", style: TextStyle(color: Colors.grey)),
              const SizedBox(height: 32),
              OutlinedButton(
                onPressed: _cancelRemoteRequest,
                child: const Text("Annuler"),
              )
            ],
          ),
        );
      }

      // Configuration Initiale
      return Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          children: [
            const Icon(Icons.tablet_mac, size: 60, color: Colors.blue),
            const SizedBox(height: 24),
            Text("Signature via Écran Client", style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: textColor)),
            const SizedBox(height: 16),
            
            if (_availableScreens.isEmpty)
              const Text("Aucun écran configuré. Veuillez en ajouter dans les paramètres.", style: TextStyle(color: Colors.red))
            else ...[
              DropdownButton<String>(
                value: _selectedScreenToken,
                dropdownColor: isDark ? const Color(0xFF2C2C2C) : Colors.white,
                style: TextStyle(color: textColor, fontSize: 16),
                items: _availableScreens.map((s) {
                  IconData icon = Icons.tablet_mac;
                  if (s['device_type'] == 'smartphone') icon = Icons.phone_iphone;
                  if (s['device_type'] == 'monitor') icon = Icons.desktop_mac;
                  
                  return DropdownMenuItem(
                    value: s['token'] as String, 
                    child: Row(
                      children: [
                        Icon(icon, size: 16, color: Colors.grey),
                        const SizedBox(width: 8),
                        Text(s['name'] + (s['orientation'] == 'portrait' ? ' (Portrait)' : '')),
                      ],
                    )
                  );
                }).toList(),
                onChanged: (v) {
                  final s = _availableScreens.firstWhere((element) => element['token'] == v);
                  setState(() {
                    _selectedScreenToken = v;
                    _selectedScreenName = s['name'];
                  });
                },
              ),
              const SizedBox(height: 32),
              ElevatedButton.icon(
                onPressed: _startRemoteRequest,
                style: ElevatedButton.styleFrom(padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 20)),
                icon: const Icon(Icons.send_to_mobile),
                label: const Text("Demander Signature"),
              ),
            ],
            
            const Spacer(),
            TextButton(
              onPressed: () => setState(() => _useRemoteScreen = false),
              child: const Text("Utiliser la signature locale (Souris/Pad)"),
            )
          ],
        ),
      );
    }
  
    // Version Locale Standard
    return SingleChildScrollView(
      padding: const EdgeInsets.all(32),
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.end,
            children: [
              TextButton.icon(
                onPressed: () => setState(() => _useRemoteScreen = true),
                icon: const Icon(Icons.tablet_mac),
                label: const Text("Utiliser un écran client"),
              )
            ],
          ),
          const SizedBox(height: 10),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Signature Pad
              Expanded(
                flex: 3,
                child: Column(
                  children: [
// ... existing local flow ...
                    Text("Signature du Client", style: TextStyle(color: textColor, fontWeight: FontWeight.bold)),
                    const SizedBox(height: 8),
                    Container(
                      decoration: BoxDecoration(border: Border.all(color: Colors.grey)),
                      child: Signature(
                        controller: _signatureController,
                        height: 300,
                        backgroundColor: Colors.white,
                      ),
                    ),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.end,
                      children: [
                        TextButton(onPressed: () => _signatureController.clear(), child: const Text("Effacer")),
                      ],
                    )
                  ],
                ),
              ),
              const SizedBox(width: 16),
              // Camera Preview for Client
              Expanded(
                flex: 2,
                child: Column(
                  children: [
                    Text("Photo Client", style: TextStyle(color: textColor, fontWeight: FontWeight.bold)),
                    const SizedBox(height: 8),
                    Container(
                      height: 300,
                      decoration: BoxDecoration(
                        border: Border.all(color: Colors.grey),
                        color: Colors.black,
                      ),
                      child: _photoClient != null
                         ? Stack(
                             fit: StackFit.expand,
                             children: [
                               kIsWeb ? Image.network(_photoClient!.path, fit: BoxFit.cover) : Image.file(File(_photoClient!.path), fit: BoxFit.cover),
                               Positioned(
                                 top: 5, right: 5,
                                 child: IconButton(onPressed: () => setState(() => _photoClient = null), icon: const Icon(Icons.delete, color: Colors.red)),
                               )
                             ],
                           )
                         : (_isCameraInitialized && _cameraController != null
                             ? CameraPreview(_cameraController!)
                             : Center(
                                 child: Column(
                                   mainAxisAlignment: MainAxisAlignment.center,
                                   children: [
                                     const Text("Caméra non disponible", style: TextStyle(color: Colors.white)),
                                     const SizedBox(height: 8),
                                     ElevatedButton.icon(
                                       icon: const Icon(Icons.upload_file),
                                       label: const Text("Importer Photo"),
                                       onPressed: () async {
                                          final file = await _picker.pickImage(source: ImageSource.gallery);
                                          if (file != null) setState(() => _photoClient = file);
                                       }
                                     )
                                   ],
                                 )
                               )),
                    ),
                    const SizedBox(height: 8),
                    ElevatedButton.icon(
                      onPressed: _photoClient == null ? _captureClientPhoto : null,
                      icon: const Icon(Icons.camera),
                      label: const Text("Capturer"),
                    )
                  ],
                ),
              )
            ],
          )
        ],
      ),
    );
  }

  // --- REMOTE UTILS ---

  Future<void> _startRemoteRequest() async {
    if (_selectedScreenToken == null) return;
    
    setState(() => _isWaitingForRemoteSignature = true);
    
    try {
      // 1. Send Status to Screen
      await _apiService.setScreenState(_selectedScreenToken!, 'SIGNATURE_MODE', data: {
        'message': "Signez pour rachat ${_marqueController.text}",
        'price': _prixController.text,
      });
      
      // 2. Start Polling
      _remotePollTimer = Timer.periodic(const Duration(seconds: 2), (timer) {
         _pollRemoteStatus();
      });
      
    } catch (e) {
      if(mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Erreur connexion écran: $e")));
        setState(() => _isWaitingForRemoteSignature = false);
      }
    }
  }

  Future<void> _cancelRemoteRequest() async {
     _remotePollTimer?.cancel();
     if (_selectedScreenToken != null) {
       await _apiService.setScreenState(_selectedScreenToken!, 'IDLE');
     }
     if (mounted) setState(() => _isWaitingForRemoteSignature = false);
  }

  Future<void> _pollRemoteStatus() async {
     if (_selectedScreenToken == null || !_isWaitingForRemoteSignature) return;

     try {
       // We poll the status endpoint check ? No, api/v2/screens/state is GET by tablet. 
       // We need to check if the state has changed to 'IDLE' WITH data.
       // Actually interaction.php sets status to IDLE and puts data in current_action_data.
       // So we check getScreenDetails aka screensGetEndpoint is not ideal because it requires ID.
       // Do we have ID? Yes _availableScreens contains ID.
       
       final screenId = _availableScreens.firstWhere((s) => s['token'] == _selectedScreenToken)['id'];
       final details = await _apiService.getScreenDetails(screenId);
       final data = details['current_action_data']; // json decoded map
       
       // interaction.php sets 'action' => 'signature_received'
       if (data != null && data['action'] == 'signature_received') {
          _remotePollTimer?.cancel();
          await _downloadRemoteFiles(data['files']);
       }
     } catch (e) {
       print("Polling err: $e");
     }
  }

  Future<void> _downloadRemoteFiles(Map<String, dynamic> files) async {
    try {
       // files = {'signature': 'remote_sig_....jpg', 'photo_client': ...}
       // Build full URL
       final baseUrl = "${ApiConfig.siteUrl}/assets/images/rachat/"; // Assuming this path
       
       Uint8List? sigBytes;
       XFile? clientPhotoFile;

       if (files['signature'] != null) {
          final resp = await http.get(Uri.parse(baseUrl + files['signature']));
          sigBytes = resp.bodyBytes;
       }

       if (files['photo_client'] != null) {
          final resp = await http.get(Uri.parse(baseUrl + files['photo_client']));
          final tempDir = await getTemporaryDirectory();
          final file = File('${tempDir.path}/${files['photo_client']}');
          await file.writeAsBytes(resp.bodyBytes);
          clientPhotoFile = XFile(file.path);
       }
       
       if (mounted) {
         setState(() {
           _remoteSignatureBytes = sigBytes;
           _photoClient = clientPhotoFile;
           _isWaitingForRemoteSignature = false; 
           // We keep _useRemoteScreen = true, but show Success View
         });
       }

    } catch (e) {
       if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Erreur téléchargement: $e")));
    }
  }
}
