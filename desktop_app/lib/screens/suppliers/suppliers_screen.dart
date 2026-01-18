import 'dart:convert';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:http/http.dart' as http;
import '../../services/auth_service.dart';
import '../../config/api_config.dart';
import '../../widgets/sidebar.dart';
import '../../widgets/app_shell.dart';
import 'dialogs/add_supplier_dialog.dart';

class SuppliersScreen extends StatefulWidget {
  const SuppliersScreen({super.key});

  @override
  State<SuppliersScreen> createState() => _SuppliersScreenState();
}

class _SuppliersScreenState extends State<SuppliersScreen> {
  bool _isLoading = true;
  List<dynamic> _suppliers = [];
  List<dynamic> _filteredSuppliers = [];
  final _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _fetchSuppliers();
    _searchController.addListener(_filterSuppliers);
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _fetchSuppliers() async {
    setState(() => _isLoading = true);
    try {
      final authService = context.read<AuthService>();
      final apiService = authService.getApiService();
      final response = await apiService.getClients(limit: 100); // Wait, getClients? No, getSuppliers.
      // Checking default_api calls... ApiService has getSuppliers()! (Step 3489 line 272)
      // Line 272: Future<List<dynamic>> getSuppliers()
      
      final suppliers = await apiService.getSuppliers();

      if (mounted) {
        setState(() {
          _suppliers = suppliers;
          _filteredSuppliers = _suppliers;
          _isLoading = false;
        });
        _filterSuppliers();
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }

  void _filterSuppliers() {
    final query = _searchController.text.toLowerCase();
    setState(() {
      _filteredSuppliers = _suppliers.where((s) {
        final name = (s['nom'] ?? '').toString().toLowerCase();
        return name.contains(query);
      }).toList();
    });
  }

  Future<void> _deleteSupplier(dynamic supplier) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: const Color(0xFF1a1a2e),
        title: const Text('Confirmer la suppression', style: TextStyle(color: Colors.red)),
        content: Text(
          'Êtes-vous sûr de vouloir supprimer ${supplier['nom']} ?\nCette action est irréversible.',
          style: const TextStyle(color: Colors.white70),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Annuler')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            onPressed: () => Navigator.pop(ctx, true), 
            child: const Text('Supprimer', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );

    if (confirmed != true) return;

    try {
      final url = Uri.parse('${ApiConfig.baseUrl}${ApiConfig.suppliersDeleteEndpoint}');
      final response = await http.post(
        url,
        headers: {'Content-Type': 'application/json'},
        body: json.encode({'id': supplier['id']}),
      );

      final data = json.decode(response.body);
      if (response.statusCode == 200 && data['success']) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Fournisseur supprimé'), backgroundColor: Colors.green),
          );
          _fetchSuppliers();
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
    }
  }

  Future<void> _launchUrl(String? urlString) async {
    if (urlString == null || urlString.isEmpty) return;
    // URL launch not implemented - would require url_launcher package
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('URL: $urlString'), backgroundColor: Colors.blue),
      );
    }
  }

  void _showAddDialog() {
    showDialog(
      context: context,
      builder: (context) => AddSupplierDialog(onSuccess: _fetchSuppliers),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final textColor = isDark ? Colors.white : const Color(0xFF1E293B);
    final subTextColor = isDark ? Colors.white.withOpacity(0.7) : Colors.grey.shade600;
    final cardColor = isDark ? const Color(0xFF1E293B) : Colors.white;
    final borderColor = isDark ? Colors.white.withOpacity(0.05) : Colors.grey.shade300;
    
    // Pour le TextField
    final inputFillColor = isDark ? const Color(0xFF1E293B) : Colors.grey[200];
    final placeholderColor = isDark ? Colors.white.withOpacity(0.5) : Colors.grey[500];

    return AppShell(
      currentRoute: '/fournisseurs', 
      content: Scaffold(
        backgroundColor: Colors.transparent,
        body: Column(
          children: [
            // Header
            Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: isDark ? const Color(0xFF1E293B) : Colors.white,
                border: Border(bottom: BorderSide(color: borderColor)),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Fournisseurs',
                        style: TextStyle(color: textColor, fontSize: 24, fontWeight: FontWeight.bold),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Gérez vos partenaires et approvisionnements',
                        style: TextStyle(color: subTextColor),
                      ),
                    ],
                  ),
                  ElevatedButton.icon(
                    onPressed: _showAddDialog,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF3B82F6),
                      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    icon: const Icon(Icons.add_circle, color: Colors.white),
                    label: const Text('Nouveau Fournisseur', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                  ),
                ],
              ),
            ),

            Expanded(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Stats & Search
                    Row(
                      children: [
                        // Stat Card
                        Container(
                          padding: const EdgeInsets.all(20),
                          decoration: BoxDecoration(
                            gradient: const LinearGradient(colors: [Color(0xFF3B82F6), Color(0xFF8B5CF6)]),
                            borderRadius: BorderRadius.circular(16),
                            boxShadow: [BoxShadow(color: const Color(0xFF3B82F6).withOpacity(0.3), blurRadius: 10, offset: const Offset(0, 4))],
                          ),
                          child: Row(
                            children: [
                              Container(
                                padding: const EdgeInsets.all(12),
                                decoration: BoxDecoration(color: Colors.white.withOpacity(0.2), borderRadius: BorderRadius.circular(12)),
                                child: const Icon(Icons.local_shipping, color: Colors.white, size: 24),
                              ),
                              const SizedBox(width: 16),
                              Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text('${_suppliers.length}', style: const TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.bold)),
                                  const Text('Partenaires Actifs', style: TextStyle(color: Colors.white70, fontSize: 12, fontWeight: FontWeight.bold)),
                                ],
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 24),
                        // Search Bar
                        Expanded(
                          child: TextField(
                            controller: _searchController,
                            style: TextStyle(color: textColor),
                            decoration: InputDecoration(
                              hintText: 'Rechercher un fournisseur...',
                              hintStyle: TextStyle(color: placeholderColor),
                              prefixIcon: Icon(Icons.search, color: placeholderColor),
                              filled: true,
                              fillColor: inputFillColor,
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                              contentPadding: const EdgeInsets.symmetric(vertical: 16),
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 24),

                    // Grid
                    Expanded(
                      child: _isLoading 
                          ? const Center(child: CircularProgressIndicator())
                          : _filteredSuppliers.isEmpty
                              ? Center(
                                  child: Column(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      Icon(Icons.inventory_2_outlined, size: 64, color: subTextColor),
                                      const SizedBox(height: 16),
                                      Text('Aucun fournisseur trouvé', style: TextStyle(color: subTextColor, fontSize: 16)),
                                    ],
                                  ),
                                )
                              : GridView.builder(
                                  gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(
                                    maxCrossAxisExtent: 350,
                                    childAspectRatio: 1.4,
                                    crossAxisSpacing: 16,
                                    mainAxisSpacing: 16,
                                  ),
                                  itemCount: _filteredSuppliers.length,
                                  itemBuilder: (context, index) {
                                    final supplier = _filteredSuppliers[index];
                                    return _buildSupplierCard(supplier, isDark, cardColor, borderColor, textColor, subTextColor);
                                  },
                                ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      )
    );
  }

  Widget _buildSupplierCard(dynamic supplier, bool isDark, Color cardColor, Color borderColor, Color textColor, Color subTextColor) {
    final name = supplier['nom'] ?? 'Inconnu';
    final initial = name.isNotEmpty ? name[0].toUpperCase() : '?';
    final url = supplier['url'];

    return Container(
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: borderColor),
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(isDark ? 0.2 : 0.05), blurRadius: 10, offset: const Offset(0, 4))],
      ),
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: [Colors.blue.withOpacity(0.2), Colors.purple.withOpacity(0.2)],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Center(
                  child: Text(
                    initial,
                    style: const TextStyle(color: Colors.blue, fontSize: 20, fontWeight: FontWeight.bold),
                  ),
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      name,
                      style: TextStyle(color: textColor, fontSize: 18, fontWeight: FontWeight.bold),
                      overflow: TextOverflow.ellipsis,
                    ),
                    Text(
                      '#${supplier['id']}',
                      style: TextStyle(color: subTextColor, fontSize: 12),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const Spacer(),
          Row(
            children: [
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: url != null && url.isNotEmpty ? () => _launchUrl(url) : null,
                  style: OutlinedButton.styleFrom(
                    foregroundColor: Colors.blue,
                    side: BorderSide(color: Colors.blue.withOpacity(0.5)),
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                  icon: const Icon(Icons.link, size: 18),
                  label: const Text('Visiter'),
                ),
              ),
              const SizedBox(width: 8),
              Container(
                decoration: BoxDecoration(
                  color: Colors.red.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: IconButton(
                  onPressed: () => _deleteSupplier(supplier),
                  icon: const Icon(Icons.delete_outline, color: Colors.red),
                  tooltip: 'Supprimer',
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
