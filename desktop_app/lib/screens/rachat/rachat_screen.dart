import 'package:flutter/material.dart';
import 'package:geekboard_desktop/widgets/app_shell.dart';
import 'package:geekboard_desktop/services/api_service.dart';
import 'package:geekboard_desktop/config/api_config.dart';
import '../../widgets/rachat_filter_bar.dart';
import 'package:provider/provider.dart';
import '../../services/auth_service.dart';

class RachatScreen extends StatefulWidget {
  const RachatScreen({super.key});

  @override
  State<RachatScreen> createState() => _RachatScreenState();
}

class _RachatScreenState extends State<RachatScreen> {
  ApiService get _apiService => context.read<AuthService>().getApiService();
  
  // Data
  List<Map<String, dynamic>> _rachats = [];
  
  // Pagination
  int _currentPage = 1;
  int _totalPages = 1;
  bool _isLoading = true;
  
  // Filters State
  String _search = '';
  // bool _showFunctional = false; // Not used yet in API
  // bool _showNonFunctional = false; // Not used yet in API

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    
    try {
      final queryParams = {
        'page': _currentPage.toString(),
        'limit': '50',
        'search': _search,
      };

      final response = await _apiService.get(ApiConfig.rachatListEndpoint, queryParams);
      
      if (mounted) {
        setState(() {
          if (response['rachats'] != null) {
            _rachats = List<Map<String, dynamic>>.from(response['rachats']);
          } else {
             _rachats = [];
          }
          
          if (response['pagination'] != null) {
            _totalPages = response['pagination']['totalPages'] ?? 1;
          }
        });
      }
    } catch (e) {
      print('Error loading rachats: $e');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erreur chargement: $e'), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return AppShell(
      currentRoute: '/rachat',
      content: Scaffold(
        backgroundColor: const Color(0xFF0F172A),
        body: Column(
          children: [
            // Header Stats (Simplified for now as PHP file doesn't have elaborate stats at top)
            _buildHeader(),
            
            Expanded(
              child: Padding(
                padding: const EdgeInsets.all(24.0),
                child: Column(
                  children: [
                    // Filters
                    RachatFilterBar(
                      onFilterChanged: (search, isFunctional, isNonFunctional) {
                        setState(() {
                          _search = search;
                          _currentPage = 1;
                        });
                        _loadData();
                      },
                    ),
                    
                    // Table
                    Expanded(
                      child: Container(
                        decoration: BoxDecoration(
                          color: const Color(0xFF1E293B),
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: Colors.white.withOpacity(0.1)),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withOpacity(0.2),
                              blurRadius: 20,
                              offset: const Offset(0, 10),
                            ),
                          ],
                        ),
                        child: _isLoading 
                          ? const Center(child: CircularProgressIndicator()) 
                          : _rachats.isEmpty 
                              ? _buildEmptyState()
                              : Column(
                                  children: [
                                    _buildTableHeader(),
                                    Expanded(
                                      child: ListView.separated(
                                        padding: EdgeInsets.zero,
                                        itemCount: _rachats.length,
                                        separatorBuilder: (ctx, i) => Divider(
                                          height: 1, 
                                          thickness: 1, 
                                          color: Colors.white.withOpacity(0.05)
                                        ),
                                        itemBuilder: (context, index) {
                                          return _buildRachatRow(_rachats[index]);
                                        },
                                      ),
                                    ),
                                    _buildPagination(),
                                  ],
                                ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildHeader() {
    return Container(
      padding: const EdgeInsets.fromLTRB(24, 24, 24, 0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xFF6366f1), Color(0xFF8b5cf6)],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(Icons.history, color: Colors.white, size: 24),
              ),
              const SizedBox(width: 16),
              const Text(
                'Historique des rachats',
                style: TextStyle(
                  fontSize: 28,
                  fontWeight: FontWeight.w800,
                  color: Colors.white,
                  letterSpacing: -0.5,
                ),
              ),
            ],
          ),
          
          Row(
            children: [
              ElevatedButton.icon(
                onPressed: () {
                   ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Fonctionnalité Nouveau Rachat à venir')),
                  );
                },
                icon: const Icon(Icons.add, size: 18),
                label: const Text('Nouveau Rachat'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF6366f1),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  elevation: 4,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildTableHeader() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
      decoration: BoxDecoration(
        color: const Color(0xFF0F172A).withOpacity(0.5),
        border: Border(bottom: BorderSide(color: Colors.white.withOpacity(0.1))),
      ),
      child: Row(
        children: [
          Expanded(flex: 2, child: _buildHeaderCell('CLIENT')), // Date included in client col for mobile feel or separate? Web uses separate col.
          Expanded(flex: 1, child: _buildHeaderCell('DATE')),
          Expanded(flex: 2, child: _buildHeaderCell('APPAREIL')),
          Expanded(flex: 2, child: _buildHeaderCell('IMEI / SIN')),
          Expanded(flex: 1, child: _buildHeaderCell('PRIX')), 
          const SizedBox(width: 50), // Action button space
        ],
      ),
    );
  }

  Widget _buildHeaderCell(String text) {
    return Text(
      text,
      style: TextStyle(
        color: Colors.grey[500],
        fontSize: 11,
        fontWeight: FontWeight.bold,
        letterSpacing: 1,
      ),
    );
  }

  Widget _buildRachatRow(Map<String, dynamic> rachat) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
      child: Row(
        children: [
          // Client
          Expanded(
            flex: 2,
            child: Row(
              children: [
                Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(colors: [Color(0xFFe0e7ff), Color(0xFFc7d2fe)]),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Center(
                    child: Text(
                      (rachat['client_prenom'] ?? 'C').toString().substring(0, 1).toUpperCase(),
                      style: const TextStyle(color: Color(0xFF4338ca), fontWeight: FontWeight.bold),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        '${rachat['client_prenom']} ${rachat['client_nom']}',
                        style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600, fontSize: 13),
                        maxLines: 1, overflow: TextOverflow.ellipsis,
                      ),
                      Text(
                        rachat['telephone'] ?? '',
                        style: TextStyle(color: Colors.grey[400], fontSize: 11),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          
          // Date
          Expanded(
            flex: 1,
            child: Text(
              rachat['date_formatted'] ?? '-',
              style: TextStyle(color: Colors.grey[400], fontSize: 12),
            ),
          ),
          
          // Apple
          Expanded(
            flex: 2,
            child: Text(
              rachat['modele'] ?? '-',
              style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w500),
            ),
          ),
          
          // IMEI
          Expanded(
            flex: 2,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(
                color: Colors.black.withOpacity(0.2),
                borderRadius: BorderRadius.circular(4),
              ),
              child: Text(
                rachat['sin'] ?? '-',
                style: TextStyle(color: Colors.grey[400], fontSize: 11, fontFamily: 'monospace'),
              ),
            ),
          ),
          
          // Price
          Expanded(
            flex: 1,
            child: Text(
              rachat['prix_formatted'] ?? '0.00 €',
              style: const TextStyle(color: Color(0xFF10B981), fontWeight: FontWeight.bold),
            ),
          ),
          
          // Actions
          SizedBox(
            width: 50,
            child: IconButton(
              icon: const Icon(Icons.print, color: Colors.blue, size: 20),
              tooltip: 'Imprimer Attestation',
              onPressed: () {
                 // Future: Print logic
              },
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.history_toggle_off, size: 64, color: Colors.grey[700]),
          const SizedBox(height: 16),
          Text(
            'Aucun rachat trouvé',
            style: TextStyle(color: Colors.grey[400], fontSize: 18, fontWeight: FontWeight.bold),
          ),
        ],
      ),
    );
  }

  Widget _buildPagination() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF0F172A).withOpacity(0.3),
        border: Border(top: BorderSide(color: Colors.white.withOpacity(0.1))),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          IconButton(
            icon: const Icon(Icons.chevron_left, color: Colors.white),
            onPressed: _currentPage > 1 ? () {
              setState(() => _currentPage--);
              _loadData();
            } : null,
          ),
          const SizedBox(width: 16),
          Text(
            'Page $_currentPage / $_totalPages',
            style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
          ),
          const SizedBox(width: 16),
          IconButton(
            icon: const Icon(Icons.chevron_right, color: Colors.white),
            onPressed: _currentPage < _totalPages ? () {
              setState(() => _currentPage++);
              _loadData();
            } : null,
          ),
        ],
      ),
    );
  }
}
