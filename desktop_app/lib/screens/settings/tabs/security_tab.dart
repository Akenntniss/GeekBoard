import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../../services/auth_service.dart';
import '../../../services/api_service.dart';
import '../../../config/api_config.dart';

class SecurityTab extends StatefulWidget {
  const SecurityTab({super.key});

  @override
  State<SecurityTab> createState() => _SecurityTabState();
}

class _SecurityTabState extends State<SecurityTab> {
  late final ApiService _apiService;
  final _currentPassCtrl = TextEditingController();
  final _newPassCtrl = TextEditingController();
  final _confirmPassCtrl = TextEditingController();
  bool _isLoading = false;

  @override
  void initState() {
      super.initState();
      _apiService = context.read<AuthService>().getApiService();
  }

  @override
  void dispose() {
    _currentPassCtrl.dispose(); _newPassCtrl.dispose(); _confirmPassCtrl.dispose();
    super.dispose();
  }

  Future<void> _updatePassword() async {
    if (_newPassCtrl.text != _confirmPassCtrl.text) {
       ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Les mots de passe ne correspondent pas")));
       return;
    }
    setState(() => _isLoading = true);
    try {
       await _apiService.post(ApiConfig.settingsUpdatePasswordEndpoint, {
         'current_password': _currentPassCtrl.text,
         'new_password': _newPassCtrl.text,
       });
       if (mounted) {
         ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Mot de passe mis à jour")));
         _currentPassCtrl.clear(); _newPassCtrl.clear(); _confirmPassCtrl.clear();
       }
     } catch (e) {
       if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Erreur: $e")));
     } finally {
       if (mounted) setState(() => _isLoading = false);
     }
  }

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      padding: const EdgeInsets.only(bottom: 24),
      child: Column(
      children: [
        _buildTextField("Mot de passe actuel", _currentPassCtrl, obscure: true),
        _buildTextField("Nouveau mot de passe", _newPassCtrl, obscure: true),
        _buildTextField("Confirmer le mot de passe", _confirmPassCtrl, obscure: true),
        const SizedBox(height: 20),
        SizedBox(
          width: double.infinity,
          child: ElevatedButton(
            onPressed: _isLoading ? null : _updatePassword,
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.blue,
              padding: const EdgeInsets.symmetric(vertical: 16),
            ),
            child: _isLoading 
              ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
              : const Text("Mettre à jour le mot de passe"),
          ),
        ),
      ],
      ),
    );
  }

  Widget _buildTextField(String label, TextEditingController ctrl, {bool obscure = false}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: TextStyle(color: Theme.of(context).textTheme.bodyMedium?.color, fontSize: 14)),
          const SizedBox(height: 8),
          TextField(
            controller: ctrl,
            obscureText: obscure,
            style: TextStyle(color: Theme.of(context).textTheme.bodyLarge?.color),
            decoration: InputDecoration(
              filled: true,
              fillColor: Theme.of(context).cardColor,
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide(color: Theme.of(context).dividerColor)),
              contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            ),
          ),
        ],
      ),
    );
  }
}
