import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:geekboard_desktop/config/api_config.dart';

class AddClientDialog extends StatefulWidget {
  final Map<String, dynamic>? client;
  final VoidCallback onSuccess;

  const AddClientDialog({super.key, this.client, required this.onSuccess});

  @override
  State<AddClientDialog> createState() => _AddClientDialogState();
}

class _AddClientDialogState extends State<AddClientDialog> {
  final _formKey = GlobalKey<FormState>();
  bool _isLoading = false;
  
  late TextEditingController _nomController;
  late TextEditingController _prenomController;
  late TextEditingController _phoneController;
  late TextEditingController _emailController;
  late TextEditingController _addressController;

  @override
  void initState() {
    super.initState();
    _nomController = TextEditingController(text: widget.client?['nom'] ?? '');
    _prenomController = TextEditingController(text: widget.client?['prenom'] ?? '');
    _phoneController = TextEditingController(text: widget.client?['telephone'] ?? '');
    _emailController = TextEditingController(text: widget.client?['email'] ?? '');
    _addressController = TextEditingController(text: widget.client?['adresse'] ?? '');
  }

  @override
  void dispose() {
    _nomController.dispose();
    _prenomController.dispose();
    _phoneController.dispose();
    _emailController.dispose();
    _addressController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);

    try {
      final isEdit = widget.client != null;
      final endpoint = isEdit ? ApiConfig.clientsUpdateEndpoint : ApiConfig.clientsCreateEndpoint;
      final url = Uri.parse('${ApiConfig.baseUrl}$endpoint');
      
      final body = {
        'nom': _nomController.text,
        'prenom': _prenomController.text,
        'telephone': _phoneController.text,
        'email': _emailController.text,
        'adresse': _addressController.text,
      };

      if (isEdit) {
        body['id'] = widget.client!['id'];
      }

      final response = await http.post(
        url,
        headers: {'Content-Type': 'application/json'},
        body: json.encode(body),
      );

      final data = json.decode(response.body);
      
      if (response.statusCode == 200 || response.statusCode == 201) {
         // Some APIs return success: true, others just data. checking status code is safer given previous patterns
        if (mounted) {
          Navigator.pop(context);
          widget.onSuccess();
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(isEdit ? 'Client modifié avec succès' : 'Client créé avec succès'), 
              backgroundColor: Colors.green
            ),
          );
        }
      } else {
        throw Exception(data['message'] ?? 'Erreur inconnue');
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isEdit = widget.client != null;
    return Dialog(
      backgroundColor: const Color(0xFF1a1a2e),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Container(
        width: 600,
        padding: const EdgeInsets.all(24),
        child: Form(
          key: _formKey,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                   Text(
                    isEdit ? 'Modifier le Client' : 'Nouveau Client',
                    style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close, color: Colors.white),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),
              const SizedBox(height: 24),
              Row(
                children: [
                  Expanded(child: _buildTextField('Prénom *', _prenomController, required: true)),
                  const SizedBox(width: 16),
                  Expanded(child: _buildTextField('Nom *', _nomController, required: true)),
                ],
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  Expanded(child: _buildTextField('Téléphone *', _phoneController, required: true, icon: Icons.phone)),
                  const SizedBox(width: 16),
                  Expanded(child: _buildTextField('Email', _emailController, icon: Icons.email)),
                ],
              ),
              const SizedBox(height: 16),
              _buildTextField('Adresse', _addressController, maxLines: 2, icon: Icons.location_on),
              const SizedBox(height: 32),
              Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  TextButton(
                    onPressed: () => Navigator.pop(context),
                    child: const Text('Annuler', style: TextStyle(color: Colors.grey)),
                  ),
                  const SizedBox(width: 16),
                  ElevatedButton(
                    onPressed: _isLoading ? null : _submit,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF4f46e5),
                      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
                    ),
                    child: _isLoading 
                        ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                        : Text(isEdit ? 'Enregistrer' : 'Créer', style: const TextStyle(color: Colors.white)),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildTextField(String label, TextEditingController controller, {bool required = false, IconData? icon, int maxLines = 1}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: const TextStyle(color: Colors.grey, fontSize: 13)),
        const SizedBox(height: 8),
        TextFormField(
          controller: controller,
          style: const TextStyle(color: Colors.white),
          maxLines: maxLines,
          validator: required ? (v) => v?.isEmpty ?? true ? 'Requis' : null : null,
          decoration: InputDecoration(
            prefixIcon: icon != null ? Icon(icon, color: Colors.grey, size: 20) : null,
            filled: true,
            fillColor: Colors.white.withOpacity(0.05),
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide.none),
            contentPadding: const EdgeInsets.all(16),
          ),
        ),
      ],
    );
  }
}
