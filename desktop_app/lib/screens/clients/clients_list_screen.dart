/// Écran Liste des Clients
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import '../../services/auth_service.dart';
import '../../models/client.dart';
import '../../widgets/sidebar.dart';

class ClientsListScreen extends StatefulWidget {
  const ClientsListScreen({super.key});

  @override
  State<ClientsListScreen> createState() => _ClientsListScreenState();
}

class _ClientsListScreenState extends State<ClientsListScreen> {
  List<Client> _clients = [];
  bool _isLoading = true;
  String? _error;
  final _searchController = TextEditingController();
  int _currentPage = 1;
  int _totalPages = 1;

  @override
  void initState() {
    super.initState();
    _loadClients();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadClients() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final authService = context.read<AuthService>();
      final apiService = authService.getApiService();
      final response = await apiService.getClients(
        page: _currentPage,
        search: _searchController.text,
      );

      setState(() {
        _clients = (response['data'] as List?)
                ?.map((json) => Client.fromJson(json))
                .toList() ??
            [];
        _totalPages = response['pagination']?['total_pages'] ?? 1;
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Row(
        children: [
          const Sidebar(currentRoute: '/clients'),
          Expanded(
            child: Container(
              color: const Color(0xFFF5F7FA),
              child: Column(
                children: [
                  // Header
                  Container(
                    padding: const EdgeInsets.all(24),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.05),
                          blurRadius: 10,
                        ),
                      ],
                    ),
                    child: Column(
                      children: [
                        Row(
                          children: [
                            Text(
                              'Clients',
                              style: GoogleFonts.poppins(
                                fontSize: 28,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                            const Spacer(),
                            IconButton(
                              icon: const Icon(Icons.refresh),
                              onPressed: _loadClients,
                            ),
                          ],
                        ),
                        const SizedBox(height: 16),
                        // Recherche
                        TextField(
                          controller: _searchController,
                          decoration: InputDecoration(
                            hintText: 'Rechercher un client...',
                            prefixIcon: const Icon(Icons.search),
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                            filled: true,
                            fillColor: Colors.grey[50],
                          ),
                          onSubmitted: (_) => _loadClients(),
                        ),
                      ],
                    ),
                  ),

                  // Liste
                  Expanded(
                    child: _isLoading
                        ? const Center(child: CircularProgressIndicator())
                        : _error != null
                            ? Center(
                                child: Column(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    Text(_error!),
                                    ElevatedButton(
                                      onPressed: _loadClients,
                                      child: const Text('Réessayer'),
                                    ),
                                  ],
                                ),
                              )
                            : _clients.isEmpty
                                ? const Center(
                                    child: Text('Aucun client trouvé'))
                                : ListView.builder(
                                    padding: const EdgeInsets.all(24),
                                    itemCount: _clients.length,
                                    itemBuilder: (context, index) {
                                      final client = _clients[index];
                                      return Card(
                                        margin:
                                            const EdgeInsets.only(bottom: 12),
                                        shape: RoundedRectangleBorder(
                                          borderRadius:
                                              BorderRadius.circular(12),
                                        ),
                                        child: ListTile(
                                          contentPadding:
                                              const EdgeInsets.all(16),
                                          leading: CircleAvatar(
                                            radius: 28,
                                            backgroundColor:
                                                const Color(0xFF667eea),
                                            child: Text(
                                              (client.nom.isNotEmpty
                                                      ? client.nom[0]
                                                      : '?')
                                                  .toUpperCase(),
                                              style: const TextStyle(
                                                color: Colors.white,
                                                fontSize: 20,
                                                fontWeight: FontWeight.bold,
                                              ),
                                            ),
                                          ),
                                          title: Text(
                                            client.fullName,
                                            style: GoogleFonts.poppins(
                                              fontWeight: FontWeight.w600,
                                            ),
                                          ),
                                          subtitle: Column(
                                            crossAxisAlignment:
                                                CrossAxisAlignment.start,
                                            children: [
                                              if (client.telephone != null)
                                                Row(
                                                  children: [
                                                    Icon(Icons.phone,
                                                        size: 14,
                                                        color: Colors.grey[500]),
                                                    const SizedBox(width: 4),
                                                    Text(client.telephone!),
                                                  ],
                                                ),
                                              if (client.email != null)
                                                Row(
                                                  children: [
                                                    Icon(Icons.email,
                                                        size: 14,
                                                        color: Colors.grey[500]),
                                                    const SizedBox(width: 4),
                                                    Text(client.email!),
                                                  ],
                                                ),
                                            ],
                                          ),
                                          trailing: Column(
                                            mainAxisAlignment:
                                                MainAxisAlignment.center,
                                            crossAxisAlignment:
                                                CrossAxisAlignment.end,
                                            children: [
                                              Container(
                                                padding:
                                                    const EdgeInsets.symmetric(
                                                  horizontal: 12,
                                                  vertical: 4,
                                                ),
                                                decoration: BoxDecoration(
                                                  color: const Color(0xFF667eea)
                                                      .withValues(alpha: 0.1),
                                                  borderRadius:
                                                      BorderRadius.circular(20),
                                                ),
                                                child: Text(
                                                  '${client.nbReparations ?? 0} réparations',
                                                  style: const TextStyle(
                                                    color: Color(0xFF667eea),
                                                    fontSize: 12,
                                                    fontWeight: FontWeight.w500,
                                                  ),
                                                ),
                                              ),
                                            ],
                                          ),
                                        ),
                                      );
                                    },
                                  ),
                  ),

                  // Pagination
                  if (!_isLoading && _totalPages > 1)
                    Container(
                      padding: const EdgeInsets.all(16),
                      color: Colors.white,
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          IconButton(
                            icon: const Icon(Icons.chevron_left),
                            onPressed: _currentPage > 1
                                ? () {
                                    setState(() => _currentPage--);
                                    _loadClients();
                                  }
                                : null,
                          ),
                          Text('Page $_currentPage / $_totalPages'),
                          IconButton(
                            icon: const Icon(Icons.chevron_right),
                            onPressed: _currentPage < _totalPages
                                ? () {
                                    setState(() => _currentPage++);
                                    _loadClients();
                                  }
                                : null,
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
    );
  }
}
