import 'package:flutter/material.dart';
import 'dart:async';
import '../services/api_service.dart';
import '../services/api_service.dart';
import '../theme/macos_theme.dart';
import '../widgets/new_client_modal.dart';

// Task blue color scheme
const Color _taskBlue = Color(0xFF007AFF);
const Color _taskBlueLight = Color(0xFF5AC8FA);

class CreateCommandDialog extends StatefulWidget {
  final ApiService apiService;
  final Map<String, dynamic>? initialClient;
  final String? initialPieceName;

  const CreateCommandDialog({
    Key? key, 
    required this.apiService,
    this.initialClient,
    this.initialPieceName,
  }) : super(key: key);

  @override
  _CreateCommandDialogState createState() => _CreateCommandDialogState();
}

class _CreateCommandDialogState extends State<CreateCommandDialog> {
  final _formKey = GlobalKey<FormState>();
  
  late TextEditingController _pieceNameController;
  final TextEditingController _barcodeController = TextEditingController();
  final TextEditingController _quantityController = TextEditingController(text: '1');
  final TextEditingController _priceController = TextEditingController();
  final TextEditingController _notesController = TextEditingController();
  
  // Client search
  final TextEditingController _clientSearchController = TextEditingController();
  Map<String, dynamic>? _selectedClient;
  List<Map<String, dynamic>> _clientSearchResults = [];
  bool _isSearchingClients = false;
  Timer? _debounce;
  bool _showClientDropdown = false;

  // Supplier selection
  String? _selectedSupplierId;
  List<dynamic> _suppliers = [];
  bool _isLoadingSuppliers = true;

  String _status = 'en_attente';
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    _loadSuppliers();
    
    // Pre-fill data if provided
    if (widget.initialClient != null) {
      _selectClient(widget.initialClient!);
    }
    
    _pieceNameController = TextEditingController(text: widget.initialPieceName ?? '');
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _clientSearchController.dispose();
    _pieceNameController.dispose();
    _barcodeController.dispose();
    _quantityController.dispose();
    _priceController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _loadSuppliers() async {
    try {
      final suppliers = await widget.apiService.getSuppliers();
      if (mounted) {
        setState(() {
          _suppliers = suppliers;
          _isLoadingSuppliers = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoadingSuppliers = false);
      }
    }
  }

  void _onClientSearchChanged(String query) {
    if (_debounce?.isActive ?? false) _debounce!.cancel();
    
    _debounce = Timer(const Duration(milliseconds: 300), () {
      if (query.length >= 2) {
        _searchClients(query);
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
      final response = await widget.apiService.getClients(search: query, limit: 20);
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
        initialPhone: _clientSearchController.text,
      ),
    );
    
    if (result != null && mounted) {
      _selectClient(result);
    }
  }

  Future<void> _submit() async {
    if (_formKey.currentState!.validate()) {
      if (_selectedClient == null) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Veuillez sélectionner un client')));
        return;
      }
      if (_selectedSupplierId == null) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Veuillez sélectionner un fournisseur')));
        return;
      }

      setState(() => _isSubmitting = true);

      try {
        final data = {
          'client_id': _selectedClient!['id'].toString(),
          'fournisseur_id': _selectedSupplierId,
          'nom_piece': _pieceNameController.text,
          'code_barre': _barcodeController.text.isNotEmpty ? _barcodeController.text : null,
          'quantite': int.tryParse(_quantityController.text) ?? 1,
          'prix_estime': _priceController.text.isNotEmpty ? double.tryParse(_priceController.text.replaceAll(',', '.')) : null,
          'statut': _status,
          'notes': _notesController.text.isNotEmpty ? _notesController.text : null,
        };

        await widget.apiService.createCommand(data);

        if (mounted) {
          Navigator.of(context).pop(true);
        }
      } catch (e) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur: $e')));
        }
      } finally {
        if (mounted) setState(() => _isSubmitting = false);
      }
    }
  }

  int _currentStep = 0;

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final bgColor = isDark ? const Color(0xFF1E1E1E) : Colors.white;
    final textColor = isDark ? Colors.white : Colors.black87;

    return Dialog(
       shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
       child: Container(
         width: 700,
         constraints: const BoxConstraints(maxHeight: 750),
         decoration: BoxDecoration(
           color: bgColor,
           borderRadius: BorderRadius.circular(20),
         ),
         child: Column(
           mainAxisSize: MainAxisSize.min,
           children: [
             // Header with dynamic title
             Container(
               padding: const EdgeInsets.all(20),
               decoration: BoxDecoration(
                 gradient: LinearGradient(
                   colors: [_taskBlue, _taskBlueLight],
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
                     width: 44,
                     height: 44,
                     decoration: BoxDecoration(
                       color: Colors.white.withOpacity(0.2),
                       borderRadius: BorderRadius.circular(12),
                     ),
                     child: Icon(_currentStep == 0 ? Icons.person_search : Icons.shopping_cart, color: Colors.white, size: 24),
                   ),
                   const SizedBox(width: 16),
                   Expanded(
                     child: Column(
                       crossAxisAlignment: CrossAxisAlignment.start,
                       children: [
                         Text(
                           _currentStep == 0 ? 'Étape 1 : Sélection du Client' : 'Étape 2 : Détails de la Commande',
                           style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Colors.white),
                         ),
                         const SizedBox(height: 2),
                         Text(
                           _currentStep == 0 ? 'Recherchez ou créez un client' : 'Renseignez les informations de la pièce',
                           style: const TextStyle(fontSize: 12, color: Colors.white70),
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

             // Progress Bar
             LinearProgressIndicator(
                value: (_currentStep + 1) / 2,
                backgroundColor: _taskBlue.withOpacity(0.1),
                valueColor: const AlwaysStoppedAnimation<Color>(_taskBlue),
                minHeight: 4,
             ),

             // Content Form
             Flexible(
               child: SingleChildScrollView(
                 padding: const EdgeInsets.all(20),
                 child: Form(
                   key: _formKey,
                   child: AnimatedSwitcher(
                      duration: const Duration(milliseconds: 300),
                      child: _currentStep == 0 
                        ? _buildClientStep(isDark, textColor, bgColor)
                        : _buildDetailsStep(isDark, textColor, bgColor),
                   ),
                 ),
               ),
             ),
             
             // Footer Actions
             Container(
               padding: const EdgeInsets.all(20),
               decoration: BoxDecoration(
                 border: Border(top: BorderSide(color: Theme.of(context).dividerColor.withOpacity(0.3))),
               ),
               child: Row(
                 mainAxisAlignment: MainAxisAlignment.end,
                 children: [
                   if (_currentStep > 0)
                     TextButton.icon(
                       onPressed: () => setState(() => _currentStep--),
                       icon: const Icon(Icons.arrow_back),
                       label: const Text("Précédent"),
                       style: TextButton.styleFrom(padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12)),
                     )
                   else
                     TextButton(
                       onPressed: () => Navigator.of(context).pop(false),
                       style: TextButton.styleFrom(
                         padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                       ),
                       child: Text("Annuler", style: TextStyle(color: textColor.withOpacity(0.6))),
                     ),
                     
                   const SizedBox(width: 12),
                   
                   if (_currentStep == 0)
                      ElevatedButton(
                        onPressed: _selectedClient == null ? null : () => setState(() => _currentStep++),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: _taskBlue,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 14),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        child: const Row(mainAxisSize: MainAxisSize.min, children: [Text("Suivant"), SizedBox(width: 8), Icon(Icons.arrow_forward, size: 16)]),
                      )
                   else
                      ElevatedButton(
                        onPressed: _isSubmitting ? null : _submit,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: _taskBlue,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 14),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          elevation: 0,
                        ),
                        child: _isSubmitting 
                          ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                          : const Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(Icons.check, size: 18),
                                SizedBox(width: 8),
                                Text("Créer la commande", style: TextStyle(fontWeight: FontWeight.w600)),
                              ],
                            ),
                      ),
                 ],
               ),
             ),
           ],
         ),
       ),
    );
  }

  Widget _buildClientStep(bool isDark, Color textColor, Color bgColor) {
    return Column(
       key: const ValueKey(0),
       crossAxisAlignment: CrossAxisAlignment.start,
       children: [
         _buildSectionLabel('RECHERCHE CLIENT', _taskBlue),
         const SizedBox(height: 8),
         TextFormField(
           controller: _clientSearchController,
           style: TextStyle(color: textColor, fontSize: 15),
           autofocus: true,
           decoration: _buildInputDecoration(
             hint: 'Rechercher un client (nom, téléphone...)',
             icon: Icons.person,
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
                   : const Icon(Icons.search, color: Colors.grey),
           ),
           onChanged: _onClientSearchChanged,
         ),
         
         const SizedBox(height: 16),
         
         if (_selectedClient != null)
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: _taskBlue.withOpacity(0.1),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: _taskBlue.withOpacity(0.3)),
              ),
              child: Row(
                children: [
                  CircleAvatar(backgroundColor: _taskBlue, child: const Icon(Icons.person, color: Colors.white)),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text("${_selectedClient!['nom']} ${_selectedClient!['prenom'] ?? ''}", style: TextStyle(fontWeight: FontWeight.bold, color: textColor, fontSize: 16)),
                        if (_selectedClient!['telephone'] != null) Text(_selectedClient!['telephone'], style: TextStyle(color: textColor.withOpacity(0.7))),
                        if (_selectedClient!['email'] != null) Text(_selectedClient!['email'], style: TextStyle(color: textColor.withOpacity(0.7))),
                      ],
                    ),
                  ),
                  const Icon(Icons.check_circle, color: Colors.green),
                ],
              ),
            )
         else if (_clientSearchResults.isNotEmpty)
            Container(
               constraints: const BoxConstraints(maxHeight: 300),
               decoration: BoxDecoration(
                 color: bgColor,
                 borderRadius: BorderRadius.circular(12),
                 border: Border.all(color: Theme.of(context).dividerColor.withOpacity(0.3)),
               ),
               child: ListView.separated(
                 shrinkWrap: true,
                 itemCount: _clientSearchResults.length,
                 separatorBuilder: (_, __) => const Divider(height: 1),
                 itemBuilder: (context, index) {
                   final client = _clientSearchResults[index];
                   return ListTile(
                     leading: const CircleAvatar(child: Icon(Icons.person, size: 20)),
                     title: Text("${client['nom']} ${client['prenom'] ?? ''}", style: TextStyle(color: textColor)),
                     subtitle: Text(client['telephone'] ?? '', style: TextStyle(color: textColor.withOpacity(0.6))),
                     onTap: () => _selectClient(client),
                   );
                 },
               ),
            )
         else if (_clientSearchController.text.length > 2 && !_isSearchingClients)
            Center(
              child: Column(
                children: [
                  const SizedBox(height: 32),
                  const Text("Aucun client trouvé", style: TextStyle(color: Colors.grey)),
                  const SizedBox(height: 16),
                  ElevatedButton.icon(
                    onPressed: _showNewClientDialog,
                    icon: const Icon(Icons.person_add),
                    label: const Text("Créer un nouveau client"),
                    style: ElevatedButton.styleFrom(padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12)),
                  )
                ],
              ),
            )
       ],
    );
  }

  Widget _buildDetailsStep(bool isDark, Color textColor, Color bgColor) {
    return Column(
      key: const ValueKey(1),
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
         // Supplier Selection
         _buildSectionLabel('FOURNISSEUR', _taskBlue),
         const SizedBox(height: 8),
         _isLoadingSuppliers 
           ? const LinearProgressIndicator()
           : Container(
               decoration: BoxDecoration(
                 color: isDark ? Colors.white.withOpacity(0.05) : Colors.grey.withOpacity(0.05),
                 borderRadius: BorderRadius.circular(12),
                 border: Border.all(color: Theme.of(context).dividerColor.withOpacity(0.3)),
               ),
               padding: const EdgeInsets.symmetric(horizontal: 16),
               child: DropdownButtonHideUnderline(
                 child: DropdownButton<String>(
                   value: _selectedSupplierId,
                   dropdownColor: bgColor,
                   isExpanded: true,
                   style: TextStyle(color: textColor),
                   hint: Text("Sélectionner un fournisseur", style: TextStyle(color: textColor.withOpacity(0.5))),
                   icon: const Icon(Icons.arrow_drop_down, color: _taskBlue),
                   items: _suppliers.map((s) => DropdownMenuItem(
                     value: s['id'].toString(),
                     child: Row(children: [
                          Icon(Icons.store, size: 18, color: textColor.withOpacity(0.6)),
                          const SizedBox(width: 10),
                          Text(s['nom'] ?? s['nom_societe'] ?? s['nom_contact'] ?? 'Inconnu'),
                     ]),
                   )).toList(),
                   onChanged: (val) => setState(() => _selectedSupplierId = val),
                 ),
               ),
             ),
         const SizedBox(height: 20),

         const Divider(),
         const SizedBox(height: 20),

         // Piece Details
         Row(
           children: [
             Expanded(
               flex: 2,
               child: Column(
                 crossAxisAlignment: CrossAxisAlignment.start,
                 children: [
                   _buildSectionLabel('NOM DE LA PIÈCE / RÉF.', _taskBlue),
                   const SizedBox(height: 8),
                   TextFormField(
                     controller: _pieceNameController,
                     style: TextStyle(color: textColor),
                     decoration: _buildInputDecoration(
                       hint: 'Ex: Écran iPhone 13, Batterie...',
                       icon: Icons.inventory_2,
                       isDark: isDark,
                     ),
                     validator: (value) => value == null || value.isEmpty ? 'Champ requis' : null,
                   ),
                 ],
               ),
             ),
             const SizedBox(width: 16),
             Expanded(
               flex: 1,
               child: Column(
                 crossAxisAlignment: CrossAxisAlignment.start,
                 children: [
                   _buildSectionLabel('CODE BARRE', _taskBlue),
                   const SizedBox(height: 8),
                   TextFormField(
                     controller: _barcodeController,
                     style: TextStyle(color: textColor),
                     decoration: _buildInputDecoration(
                       hint: 'Optionnel',
                       icon: Icons.qr_code,
                       isDark: isDark,
                     ),
                   ),
                 ],
               ),
             ),
           ],
         ),
         const SizedBox(height: 16),

         // Quantite & Prix & Status
         Row(
           children: [
             Expanded(
               child: Column(
                 crossAxisAlignment: CrossAxisAlignment.start,
                 children: [
                   _buildSectionLabel('QUANTITÉ', _taskBlue),
                   const SizedBox(height: 8),
                   TextFormField(
                     controller: _quantityController,
                     style: TextStyle(color: textColor),
                     keyboardType: TextInputType.number,
                     decoration: _buildInputDecoration(
                       hint: '1',
                       icon: Icons.numbers,
                       isDark: isDark,
                     ),
                     validator: (value) {
                        if (value == null || value.isEmpty) return 'Requis';
                        final n = int.tryParse(value);
                        if (n == null || n <= 0) return '> 0';
                        return null;
                     },
                   ),
                 ],
               ),
             ),
             const SizedBox(width: 12),
             Expanded(
               child: Column(
                 crossAxisAlignment: CrossAxisAlignment.start,
                 children: [
                   _buildSectionLabel('PRIX ESTIMÉ', _taskBlue),
                   const SizedBox(height: 8),
                   TextFormField(
                     controller: _priceController,
                     style: TextStyle(color: textColor),
                     keyboardType: const TextInputType.numberWithOptions(decimal: true),
                     decoration: _buildInputDecoration(
                       hint: '0.00',
                       icon: Icons.euro,
                       isDark: isDark,
                     ),
                   ),
                 ],
               ),
             ),
             const SizedBox(width: 12),
             Expanded(
               child: Column(
                 crossAxisAlignment: CrossAxisAlignment.start,
                 children: [
                   _buildSectionLabel('STATUT', _taskBlue),
                   const SizedBox(height: 8),
                   Container(
                     height: 52, // Match text field height
                     decoration: BoxDecoration(
                       color: isDark ? Colors.white.withOpacity(0.05) : Colors.grey.withOpacity(0.05),
                       borderRadius: BorderRadius.circular(12),
                       border: Border.all(color: Theme.of(context).dividerColor.withOpacity(0.3)),
                     ),
                     padding: const EdgeInsets.symmetric(horizontal: 12),
                     child: DropdownButtonHideUnderline(
                       child: DropdownButton<String>(
                         value: _status,
                         dropdownColor: bgColor,
                         isExpanded: true,
                         style: TextStyle(color: textColor),
                         icon: const Icon(Icons.arrow_drop_down, color: _taskBlue),
                         items: [
                           DropdownMenuItem(value: 'en_attente', child: Text('En attente', style: TextStyle(color: textColor))),
                           DropdownMenuItem(value: 'commande', child: Text('Commandé', style: TextStyle(color: Colors.blue))),
                           DropdownMenuItem(value: 'recu', child: Text('Reçu', style: TextStyle(color: Colors.green))),
                         ],
                         onChanged: (val) => setState(() => _status = val!),
                       ),
                     ),
                   ),
                 ],
               ),
             ),
           ],
         ),
         const SizedBox(height: 16),

         // Notes
         _buildSectionLabel('NOTES INTERNES', _taskBlue),
         const SizedBox(height: 8),
         TextFormField(
           controller: _notesController,
           style: TextStyle(color: textColor),
           maxLines: 2,
           decoration: _buildInputDecoration(
             hint: 'Instructions spécifiques, détails...',
             icon: Icons.note,
             isDark: isDark,
             alignIconTop: true,
           ),
         ),
      ],
    );
  }

  Widget _buildSectionLabel(String label, Color color) {
    return Text(
      label,
      style: TextStyle(
        fontSize: 11,
        fontWeight: FontWeight.w600,
        color: color,
        letterSpacing: 0.5,
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
        child: Icon(icon, color: _taskBlue, size: 20),
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
        borderSide: BorderSide(color: _taskBlue, width: 2),
      ),
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
    );
  }
}
