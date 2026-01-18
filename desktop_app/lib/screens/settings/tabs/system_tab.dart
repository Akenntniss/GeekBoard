import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../../services/auth_service.dart';
import '../../../../config/api_config.dart';
import '../../../../services/api_service.dart';

class SystemTab extends StatefulWidget {
  const SystemTab({super.key});

  @override
  State<SystemTab> createState() => _SystemTabState();
}

class _SystemTabState extends State<SystemTab> {
  late final ApiService _apiService;
  bool _isLoading = true;
  final _formKey = GlobalKey<FormState>();
  
  // Controllers
  final TextEditingController _nameController = TextEditingController();
  final TextEditingController _addressController = TextEditingController();
  final TextEditingController _phoneController = TextEditingController();
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _websiteController = TextEditingController();
  final TextEditingController _siretController = TextEditingController();
  final TextEditingController _tvaController = TextEditingController();

  Map<String, dynamic>? _companyData;
  Map<String, dynamic>? _profileData;

  @override
  void initState() {
    super.initState();
    _apiService = context.read<AuthService>().getApiService();
    _loadData();
  }

  @override
  void dispose() {
    _nameController.dispose();
    _addressController.dispose();
    _phoneController.dispose();
    _emailController.dispose();
    _websiteController.dispose();
    _siretController.dispose();
    _tvaController.dispose();
    super.dispose();
  }

  Future<void> _loadData() async {
    try {
      final data = await _apiService.get(ApiConfig.settingsGetEndpoint);
      if (mounted && data != null && data['success'] == true) {
          final company = data['company'];
          final profile = data['profile'];
          
          setState(() {
           _companyData = company;
           _profileData = profile;
           
           // Init controllers
           _nameController.text = company['company_name'] ?? '';
           _addressController.text = company['company_address'] ?? '';
           _phoneController.text = company['company_phone'] ?? '';
           _emailController.text = company['company_email'] ?? profile['email'] ?? '';
           _websiteController.text = company['company_website'] ?? '';
           _siretController.text = company['company_siret'] ?? '';
           _tvaController.text = company['company_tva'] ?? ''; // Note: API might not update TVA yet based on update_company.php check, but we keep it UI side
           
           _isLoading = false;
         });
      }
    } catch (e) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _saveData() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);

    try {
      final payload = {
        'company_name': _nameController.text,
        'company_address': _addressController.text,
        'company_phone': _phoneController.text,
        'company_email': _emailController.text,
        // 'company_website': _websiteController.text, // API might not support this yet based on quick check
        // 'company_siret': _siretController.text,
        // 'company_number': _siretController.text, // Map siret to company_number if needed
      };
      
      // Based on update_company.php: company_number is supported. 
      // Typically SIRET is stored in company_number or its own field.
      payload['company_number'] = _siretController.text;

      final response = await _apiService.post(ApiConfig.settingsUpdateCompanyEndpoint, payload);

      if (mounted) {
        if (response['success'] == true) {
          ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Informations mises à jour avec succès"), backgroundColor: Colors.green));
        } else {
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(response['message'] ?? "Erreur lors de la mise à jour"), backgroundColor: Colors.red));
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Erreur: $e"), backgroundColor: Colors.red));
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) return const Center(child: CircularProgressIndicator());

    return SingleChildScrollView(
      padding: const EdgeInsets.only(bottom: 24),
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header / Logo area could be here
            
            _buildSection("Coordonnées de l'entreprise", [
               _buildTextField("Nom de l'entreprise", _nameController),
               _buildTextField("Adresse", _addressController, maxLines: 2),
               _buildTextField("Téléphone", _phoneController),
               _buildTextField("Email", _emailController),
               _buildTextField("Site Web", _websiteController),
               _buildTextField("SIRET", _siretController),
               // _buildTextField("TVA Intracom.", _tvaController), // Optional if not in update API
            ]),
            
            const SizedBox(height: 24),
            
            _buildSection("Paramètres Régionaux (Lecture seule)", [
               _buildInfoRow("Devise", _companyData?['currency_symbol'] ?? '€'),
               _buildInfoRow("Fuseau Horaire", "Europe/Paris (Automatique)"),
            ]),

            const SizedBox(height: 32),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: _saveData,
                style: ElevatedButton.styleFrom(
                  backgroundColor: Theme.of(context).primaryColor,
                  padding: const EdgeInsets.symmetric(vertical: 16),
                ),
                child: const Text("Enregistrer les modifications", style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white)),
              ),
            )
          ],
        ),
      ),
    );
  }

  Widget _buildSection(String title, List<Widget> children) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(title, style: TextStyle(color: Theme.of(context).primaryColor, fontSize: 18, fontWeight: FontWeight.bold)),
        const SizedBox(height: 16),
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Theme.of(context).cardColor,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: Theme.of(context).dividerColor),
          ),
          child: Column(
            children: [
              for (var i = 0; i < children.length; i++) ...[
                 if (i > 0) const SizedBox(height: 16),
                 children[i],
              ]
            ],
          ),
        )
      ],
    );
  }

  Widget _buildTextField(String label, TextEditingController controller, {int maxLines = 1}) {
    return TextFormField(
      controller: controller,
      maxLines: maxLines,
      decoration: InputDecoration(
        labelText: label,
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
      ),
    );
  }

  Widget _buildInfoRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(color: Colors.grey)),
          Text(value, style: TextStyle(color: Theme.of(context).textTheme.bodyLarge?.color, fontWeight: FontWeight.w500)),
        ],
      ),
    );
  }
}
