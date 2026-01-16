import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../theme/macos_theme.dart';

class NewClientModal extends StatefulWidget {
  final ApiService apiService;
  final String? initialPhone; // Optional: prepopulate phone

  const NewClientModal({
    Key? key, 
    required this.apiService,
    this.initialPhone,
  }) : super(key: key);

  @override
  State<NewClientModal> createState() => _NewClientModalState();
}

class _NewClientModalState extends State<NewClientModal> {
  final _formKey = GlobalKey<FormState>();
  final _nomController = TextEditingController();
  final _prenomController = TextEditingController();
  final _telephoneController = TextEditingController();
  final _emailController = TextEditingController();
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    if (widget.initialPhone != null) {
      _telephoneController.text = widget.initialPhone!;
    }
  }

  @override
  void dispose() {
    _nomController.dispose();
    _prenomController.dispose();
    _telephoneController.dispose();
    _emailController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_formKey.currentState!.validate()) {
      setState(() => _isSubmitting = true);
      
      try {
        final response = await widget.apiService.createClient({
          'nom': _nomController.text,
          'prenom': _prenomController.text,
          'telephone': _telephoneController.text,
          'email': _emailController.text.isNotEmpty ? _emailController.text : null,
        });
        
        if (mounted) {
          // Return the created client data
          Navigator.of(context).pop({
            'id': response['client_id'] ?? response['id'],
            'nom': _nomController.text,
            'prenom': _prenomController.text,
            'telephone': _telephoneController.text,
            'email': _emailController.text,
          });
        }
      } catch (e) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red));
        }
      } finally {
        if (mounted) setState(() => _isSubmitting = false);
      }
    }
  }

  InputDecoration _buildInputDecoration({required String label, required bool isDark, IconData? icon}) {
    // Shared decoration style
    return InputDecoration(
      labelText: label,
      labelStyle: TextStyle(color: isDark ? Colors.white70 : Colors.black54),
      prefixIcon: icon != null ? Icon(icon, color: Colors.blue) : null,
      filled: true,
      fillColor: isDark ? Colors.white.withOpacity(0.05) : Colors.grey.withOpacity(0.05),
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: isDark ? Colors.white24 : Colors.black12)),
      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Colors.blue, width: 2)),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final bgColor = isDark ? const Color(0xFF1E1E1E) : Colors.white;
    final textColor = isDark ? Colors.white : Colors.black87;
    const accentColor = Color(0xFF007AFF);

    return Dialog(
       shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
       child: Container(
         width: 500,
         padding: const EdgeInsets.all(24),
         decoration: BoxDecoration(
           color: bgColor,
           borderRadius: BorderRadius.circular(16),
         ),
         child: Form(
           key: _formKey,
           child: Column(
             mainAxisSize: MainAxisSize.min,
             crossAxisAlignment: CrossAxisAlignment.start,
             children: [
               Row(
                 children: [
                   Container(
                     padding: const EdgeInsets.all(8),
                     decoration: BoxDecoration(color: accentColor.withOpacity(0.1), borderRadius: BorderRadius.circular(10)),
                     child: const Icon(Icons.person_add, color: accentColor),
                   ),
                   const SizedBox(width: 12),
                   Text("Nouveau Client", style: TextStyle(color: textColor, fontSize: 18, fontWeight: FontWeight.bold)),
                 ],
               ),
               const SizedBox(height: 24),
               
               Row(
                 children: [
                   Expanded(
                     child: TextFormField(
                       controller: _nomController,
                       style: TextStyle(color: textColor),
                       decoration: _buildInputDecoration(label: 'Nom *', isDark: isDark),
                       validator: (v) => v == null || v.isEmpty ? 'Requis' : null,
                     ),
                   ),
                   const SizedBox(width: 12),
                   Expanded(
                     child: TextFormField(
                       controller: _prenomController,
                       style: TextStyle(color: textColor),
                       decoration: _buildInputDecoration(label: 'Prénom', isDark: isDark),
                     ),
                   ),
                 ],
               ),
               const SizedBox(height: 16),
               
               TextFormField(
                 controller: _telephoneController,
                 style: TextStyle(color: textColor),
                 keyboardType: TextInputType.phone,
                 decoration: _buildInputDecoration(label: 'Téléphone *', isDark: isDark, icon: Icons.phone),
                 validator: (v) => v == null || v.isEmpty ? 'Requis' : null,
               ),
               const SizedBox(height: 16),
               
               TextFormField(
                 controller: _emailController,
                 style: TextStyle(color: textColor),
                 keyboardType: TextInputType.emailAddress,
                 decoration: _buildInputDecoration(label: 'Email', isDark: isDark, icon: Icons.email),
               ),
               const SizedBox(height: 24),
               
               Row(
                 mainAxisAlignment: MainAxisAlignment.end,
                 children: [
                   TextButton(
                     onPressed: () => Navigator.pop(context),
                     child: Text("Annuler", style: TextStyle(color: textColor.withOpacity(0.6))),
                   ),
                   const SizedBox(width: 12),
                   ElevatedButton(
                     onPressed: _isSubmitting ? null : _submit,
                     style: ElevatedButton.styleFrom(
                       backgroundColor: accentColor,
                       foregroundColor: Colors.white,
                       padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                       shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                     ),
                     child: _isSubmitting 
                         ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                         : const Text("Créer le client"),
                   ),
                 ],
               ),
             ],
           ),
         ),
       ),
    );
  }
}
