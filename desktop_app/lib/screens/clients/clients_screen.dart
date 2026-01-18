import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:geekboard_desktop/config/api_config.dart';
import 'package:geekboard_desktop/widgets/sidebar.dart';
import 'package:geekboard_desktop/screens/clients/dialogs/add_client_dialog.dart';
import 'package:geekboard_desktop/screens/clients/dialogs/client_history_dialog.dart';
import 'package:provider/provider.dart';
import '../../services/auth_service.dart';

class ClientsScreen extends StatefulWidget {
  const ClientsScreen({super.key});

  @override
  State<ClientsScreen> createState() => _ClientsScreenState();
}

class _ClientsScreenState extends State<ClientsScreen> {
  bool _isLoading = true;
  List<dynamic> _clients = [];
  final _searchController = TextEditingController();
  int _currentPage = 1;
  int _totalPages = 1;
  int _totalClients = 0;

  @override
  void initState() {
    super.initState();
    _fetchClients();
  }

  Future<void> _fetchClients({int page = 1}) async {
    setState(() => _isLoading = true);
    try {
      final authService = context.read<AuthService>();
      final data = await authService.getApiService().getClients(
        page: page,
        limit: 20,
        search: _searchController.text,
      );

      if (data['success'] == true) {
        setState(() {
          _clients = data['clients'];
          _currentPage = data['pagination']['page'];
          _totalClients = data['pagination']['total'];
          _totalPages = data['pagination']['total_pages'];
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _deleteClient(dynamic client) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: const Color(0xFF1a1a2e),
        title: const Text('Confirmation', style: TextStyle(color: Colors.red)),
        content: Text('Supprimer définitivement ${client['nom']} ?', style: const TextStyle(color: Colors.white70)),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Annuler')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Supprimer'),
          ),
        ],
      ),
    );

    if (confirmed != true) return;

    try {
      final authService = context.read<AuthService>();
      final response = await authService.getApiService().post(
        ApiConfig.clientsDeleteEndpoint,
        {'id': client['id']},
      );

      if (mounted) {
        if (response['success'] == true) {
           ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Client supprimé'), backgroundColor: Colors.green));
           _fetchClients(page: _currentPage);
        } else {
           ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(response['message'] ?? 'Erreur'), backgroundColor: Colors.red));
        }
      }
    } catch (e) {
      if (mounted) {
         ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    return Scaffold(
      backgroundColor: isDark ? const Color(0xFF0F172A) : const Color(0xFFF1F5F9),
      body: Row(
        children: [
          const Sidebar(currentRoute: '/clients'),
          Expanded(
            child: Column(
              children: [
                // Header
                Container(
                  padding: const EdgeInsets.all(24),
                  decoration: BoxDecoration(
                    color: isDark ? const Color(0xFF1E293B) : Colors.white,
                    border: Border(bottom: BorderSide(color: isDark ? const Color(0xFF334155) : Colors.grey.shade200)),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Clients', style: TextStyle(color: isDark ? Colors.white : Colors.black87, fontSize: 24, fontWeight: FontWeight.bold)),
                          const SizedBox(height: 4),
                          Text('$_totalClients clients enregistrés', style: TextStyle(color: isDark ? Colors.white.withOpacity(0.7) : Colors.grey.shade600)),
                        ],
                      ),
                      ElevatedButton.icon(
                        onPressed: () => showDialog(
                          context: context, 
                          builder: (_) => AddClientDialog(onSuccess: () => _fetchClients(page: 1))
                        ),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.blue,
                          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        icon: const Icon(Icons.add, color: Colors.white),
                        label: const Text('Nouveau Client', style: TextStyle(color: Colors.white)),
                      ),
                    ],
                  ),
                ),

                // Content
                Expanded(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      children: [
                        // Search
                        TextField(
                          controller: _searchController,
                          style: TextStyle(color: isDark ? Colors.white : Colors.black87),
                          decoration: InputDecoration(
                            hintText: 'Rechercher un client (nom, tel, email)...',
                            hintStyle: TextStyle(color: isDark ? Colors.grey[400] : Colors.grey[600]),
                            prefixIcon: const Icon(Icons.search, color: Colors.grey),
                            filled: true,
                            fillColor: isDark ? const Color(0xFF1E293B) : Colors.white,
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                            enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: isDark ? Colors.transparent : Colors.grey.shade200)),
                          ),
                          onSubmitted: (_) => _fetchClients(page: 1),
                        ),
                        const SizedBox(height: 24),

                        // Table
                        Expanded(
                          child: Container(
                            decoration: BoxDecoration(
                              color: isDark ? const Color(0xFF1E293B) : Colors.white,
                              borderRadius: BorderRadius.circular(16),
                              border: isDark ? null : Border.all(color: Colors.grey.shade200),
                            ),
                            child: _isLoading 
                                ? const Center(child: CircularProgressIndicator())
                                : Column(
                                    children: [
                                      // Table Header
                                      Padding(
                                        padding: const EdgeInsets.all(16),
                                        child: Row(
                                          children: const [
                                            SizedBox(width: 60, child: Text('ID', style: TextStyle(color: Colors.grey, fontWeight: FontWeight.bold))),
                                            Expanded(flex: 2, child: Text('CLIENT', style: TextStyle(color: Colors.grey, fontWeight: FontWeight.bold))),
                                            Expanded(child: Text('TÉLÉPHONE', style: TextStyle(color: Colors.grey, fontWeight: FontWeight.bold))),
                                            Expanded(child: Text('EMAIL', style: TextStyle(color: Colors.grey, fontWeight: FontWeight.bold))),
                                            Expanded(child: Text('REPARATIONS', style: TextStyle(color: Colors.grey, fontWeight: FontWeight.bold))),
                                            SizedBox(width: 100, child: Text('ACTIONS', style: TextStyle(color: Colors.grey, fontWeight: FontWeight.bold), textAlign: TextAlign.right)),
                                          ],
                                        ),
                                      ),
                                      const Divider(height: 1, color: Color(0xFF334155)),
                                      if (!isDark) const Divider(height: 1, color: Color(0xFFE2E8F0)), // Light mode divider override
                                      
                                      // List Items
                                      Expanded(
                                        child: ListView.separated(
                                          itemCount: _clients.length,
                                          separatorBuilder: (_, __) => Divider(height: 1, color: isDark ? const Color(0xFF334155) : const Color(0xFFE2E8F0)),
                                          itemBuilder: (context, index) {
                                            final client = _clients[index];
                                              return InkWell(
                                                onTap: () => showDialog(
                                                  context: context, 
                                                  builder: (_) => ClientHistoryDialog(
                                                    clientId: client['id'],
                                                    apiService: context.read<AuthService>().getApiService(),
                                                  )
                                                ),
                                                hoverColor: isDark ? Colors.white.withOpacity(0.05) : Colors.grey.withOpacity(0.05),
                                                child: Padding(
                                                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                                                child: Row(
                                                  children: [
                                                    SizedBox(width: 60, child: Text('#${client['id']}', style: const TextStyle(color: Colors.blue, fontWeight: FontWeight.bold))),
                                                    Expanded(
                                                      flex: 2, 
                                                      child: Column(
                                                        crossAxisAlignment: CrossAxisAlignment.start,
                                                        children: [
                                                          Text('${client['prenom']} ${client['nom']}', style: TextStyle(color: isDark ? Colors.white : Colors.black87, fontWeight: FontWeight.bold)),
                                                        ],
                                                      ),
                                                    ),
                                                    Expanded(child: Text(client['telephone'] ?? '-', style: TextStyle(color: isDark ? Colors.white.withOpacity(0.9) : Colors.black87))),
                                                    Expanded(child: Text(client['email'] ?? '-', style: TextStyle(color: isDark ? Colors.white.withOpacity(0.7) : Colors.black54))),
                                                    Expanded(
                                                      child: Container(
                                                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                                        decoration: BoxDecoration(
                                                          color: Colors.blue.withOpacity(0.1),
                                                          borderRadius: BorderRadius.circular(12),
                                                        ),
                                                        child: Text(
                                                          '${client['reparations_count']} Réparations',
                                                          style: const TextStyle(color: Colors.blue, fontSize: 12, fontWeight: FontWeight.bold),
                                                        ),
                                                      ),
                                                    ),
                                                    SizedBox(
                                                      width: 100,
                                                      child: Row(
                                                        mainAxisAlignment: MainAxisAlignment.end,
                                                        children: [
                                                          IconButton(
                                                            icon: const Icon(Icons.edit, color: Colors.grey, size: 20),
                                                            onPressed: () => showDialog(
                                                              context: context, 
                                                              builder: (_) => AddClientDialog(
                                                                client: client, 
                                                                onSuccess: () => _fetchClients(page: _currentPage)
                                                              )
                                                            ),
                                                          ),
                                                          IconButton(
                                                            icon: const Icon(Icons.delete, color: Colors.red, size: 20),
                                                            onPressed: () => _deleteClient(client),
                                                          ),
                                                        ],
                                                      ),
                                                    ),
                                                  ],
                                                ),
                                              ),
                                            );
                                          },
                                        ),
                                      ),
                                    ],
                                  ),
                          ),
                        ),
                        
                        // Pagination
                        if (_totalPages > 1)
                          Padding(
                            padding: const EdgeInsets.only(top: 16),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                IconButton(
                                  icon: Icon(Icons.chevron_left, color: isDark ? Colors.white : Colors.black87),
                                  onPressed: _currentPage > 1 ? () => _fetchClients(page: _currentPage - 1) : null,
                                ),
                                Text('Page $_currentPage / $_totalPages', style: TextStyle(color: isDark ? Colors.white : Colors.black87)),
                                IconButton(
                                  icon: Icon(Icons.chevron_right, color: isDark ? Colors.white : Colors.black87),
                                  onPressed: _currentPage < _totalPages ? () => _fetchClients(page: _currentPage + 1) : null,
                                ),
                              ],
                            ),
                          ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
