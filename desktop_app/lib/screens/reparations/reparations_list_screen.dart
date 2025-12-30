/// Écran Liste des Réparations
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../../services/auth_service.dart';
import '../../models/reparation.dart';
import '../../widgets/sidebar.dart';

class ReparationsListScreen extends StatefulWidget {
  const ReparationsListScreen({super.key});

  @override
  State<ReparationsListScreen> createState() => _ReparationsListScreenState();
}

class _ReparationsListScreenState extends State<ReparationsListScreen> {
  List<Reparation> _reparations = [];
  bool _isLoading = true;
  String? _error;
  String _selectedStatus = '';
  final _searchController = TextEditingController();
  int _currentPage = 1;
  int _totalPages = 1;

  @override
  void initState() {
    super.initState();
    _loadReparations();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadReparations() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final authService = context.read<AuthService>();
      final apiService = authService.getApiService();
      final response = await apiService.getReparations(
        page: _currentPage,
        status: _selectedStatus,
        search: _searchController.text,
      );

      setState(() {
        _reparations = (response['data'] as List?)
                ?.map((json) => Reparation.fromJson(json))
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
    final dateFormat = DateFormat('dd/MM/yyyy HH:mm');

    return Scaffold(
      body: Row(
        children: [
          const Sidebar(currentRoute: '/reparations'),
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
                              'Réparations',
                              style: GoogleFonts.poppins(
                                fontSize: 28,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                            const Spacer(),
                            IconButton(
                              icon: const Icon(Icons.refresh),
                              onPressed: _loadReparations,
                            ),
                          ],
                        ),
                        const SizedBox(height: 16),
                        // Filtres
                        Row(
                          children: [
                            // Recherche
                            Expanded(
                              flex: 2,
                              child: TextField(
                                controller: _searchController,
                                decoration: InputDecoration(
                                  hintText: 'Rechercher...',
                                  prefixIcon: const Icon(Icons.search),
                                  border: OutlineInputBorder(
                                    borderRadius: BorderRadius.circular(12),
                                  ),
                                  filled: true,
                                  fillColor: Colors.grey[50],
                                ),
                                onSubmitted: (_) => _loadReparations(),
                              ),
                            ),
                            const SizedBox(width: 16),
                            // Filtre statut
                            Expanded(
                              child: DropdownButtonFormField<String>(
                                value: _selectedStatus,
                                decoration: InputDecoration(
                                  labelText: 'Statut',
                                  border: OutlineInputBorder(
                                    borderRadius: BorderRadius.circular(12),
                                  ),
                                  filled: true,
                                  fillColor: Colors.grey[50],
                                ),
                                items: const [
                                  DropdownMenuItem(
                                      value: '', child: Text('Tous')),
                                  DropdownMenuItem(
                                      value: 'en_attente',
                                      child: Text('En attente')),
                                  DropdownMenuItem(
                                      value: 'en_cours',
                                      child: Text('En cours')),
                                  DropdownMenuItem(
                                      value: 'terminee',
                                      child: Text('Terminée')),
                                  DropdownMenuItem(
                                      value: 'livre', child: Text('Livrée')),
                                ],
                                onChanged: (value) {
                                  setState(() {
                                    _selectedStatus = value ?? '';
                                    _currentPage = 1;
                                  });
                                  _loadReparations();
                                },
                              ),
                            ),
                          ],
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
                                      onPressed: _loadReparations,
                                      child: const Text('Réessayer'),
                                    ),
                                  ],
                                ),
                              )
                            : _reparations.isEmpty
                                ? const Center(
                                    child: Text('Aucune réparation trouvée'))
                                : ListView.builder(
                                    padding: const EdgeInsets.all(24),
                                    itemCount: _reparations.length,
                                    itemBuilder: (context, index) {
                                      final rep = _reparations[index];
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
                                          leading: Container(
                                            width: 56,
                                            height: 56,
                                            decoration: BoxDecoration(
                                              color: _getStatusColor(rep.status)
                                                  .withValues(alpha: 0.1),
                                              borderRadius:
                                                  BorderRadius.circular(12),
                                            ),
                                            child: Icon(
                                              Icons.smartphone,
                                              color:
                                                  _getStatusColor(rep.status),
                                              size: 28,
                                            ),
                                          ),
                                          title: Text(
                                            '#${rep.numero} - ${rep.marque ?? ''} ${rep.appareil}'
                                                .trim(),
                                            style: GoogleFonts.poppins(
                                              fontWeight: FontWeight.w600,
                                            ),
                                          ),
                                          subtitle: Column(
                                            crossAxisAlignment:
                                                CrossAxisAlignment.start,
                                            children: [
                                              const SizedBox(height: 4),
                                              Text(rep.clientFullName.isNotEmpty
                                                  ? rep.clientFullName
                                                  : 'Client inconnu'),
                                              if (rep.dateCreation != null)
                                                Text(
                                                  dateFormat
                                                      .format(rep.dateCreation!),
                                                  style: TextStyle(
                                                    color: Colors.grey[500],
                                                    fontSize: 12,
                                                  ),
                                                ),
                                            ],
                                          ),
                                          trailing: Column(
                                            mainAxisAlignment:
                                                MainAxisAlignment.center,
                                            crossAxisAlignment:
                                                CrossAxisAlignment.end,
                                            children: [
                                              Chip(
                                                label: Text(rep.statusLabel),
                                                backgroundColor:
                                                    _getStatusColor(rep.status)
                                                        .withValues(alpha: 0.1),
                                                labelStyle: TextStyle(
                                                  color: _getStatusColor(
                                                      rep.status),
                                                  fontSize: 12,
                                                ),
                                              ),
                                              if (rep.prix != null)
                                                Text(
                                                  '${rep.prix!.toStringAsFixed(2)} €',
                                                  style: GoogleFonts.poppins(
                                                    fontWeight: FontWeight.w600,
                                                    color:
                                                        const Color(0xFF667eea),
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
                                    _loadReparations();
                                  }
                                : null,
                          ),
                          Text('Page $_currentPage / $_totalPages'),
                          IconButton(
                            icon: const Icon(Icons.chevron_right),
                            onPressed: _currentPage < _totalPages
                                ? () {
                                    setState(() => _currentPage++);
                                    _loadReparations();
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

  Color _getStatusColor(String status) {
    switch (status) {
      case 'en_attente':
        return Colors.orange;
      case 'en_cours':
        return Colors.blue;
      case 'terminee':
        return Colors.green;
      case 'livre':
        return Colors.purple;
      case 'annulee':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }
}
