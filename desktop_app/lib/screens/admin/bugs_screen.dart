import 'package:flutter/material.dart';
import '../../widgets/app_shell.dart';
import '../../services/api_service.dart';
import '../../config/api_config.dart';
import '../../widgets/bug_card.dart';

class BugsScreen extends StatefulWidget {
  const BugsScreen({super.key});

  @override
  State<BugsScreen> createState() => _BugsScreenState();
}

class _BugsScreenState extends State<BugsScreen> {
  final ApiService _apiService = ApiService();
  final ScrollController _scrollController = ScrollController();
  
  List<dynamic> _bugs = [];
  bool _isLoading = true;
  int _currentPage = 1;
  int _totalPages = 1;
  
  String _selectedStatus = 'all';

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData({bool refresh = false}) async {
    if (refresh) {
      _currentPage = 1;
      _bugs = [];
    }
    
    setState(() => _isLoading = true);
    
    try {
      final queryParams = {
        'page': _currentPage.toString(),
        'limit': '20',
        'status': _selectedStatus,
      };
      
      final response = await _apiService.get(ApiConfig.bugsListEndpoint, queryParams);
      
      if (mounted) {
        setState(() {
          final newBugs = response['bugs'] as List? ?? [];
          if (refresh) {
            _bugs = newBugs;
          } else {
            _bugs.addAll(newBugs);
          }
          final meta = response['meta'] ?? {};
          _totalPages = meta['total_pages'] ?? 1;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur: $e')));
      }
    }
  }

  Future<void> _updateStatus(int id, String newStatus) async {
    try {
      await _apiService.post(ApiConfig.bugsUpdateEndpoint, {
        'id': id,
        'action': 'update_status',
        'status': newStatus
      });
      _loadData(refresh: true); // Refresh to reflect changes and potentially filter out
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur maj: $e')));
    }
  }

  Future<void> _deleteBug(int id) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        backgroundColor: const Color(0xFF1E293B),
        title: const Text('Confirmer', style: TextStyle(color: Colors.white)),
        content: const Text('Supprimer ce rapport ?', style: TextStyle(color: Colors.grey)),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Non')),
          TextButton(
             onPressed: () => Navigator.pop(context, true), 
             child: const Text('Oui', style: TextStyle(color: Colors.red))
          ),
        ],
      ),
    );

    if (confirm == true) {
      try {
        await _apiService.post(ApiConfig.bugsUpdateEndpoint, {
          'id': id,
          'action': 'delete',
        });
        _loadData(refresh: true);
      } catch (e) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur suppression: $e')));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return AppShell(
      currentRoute: '/bugs',
      content: Scaffold(
        backgroundColor: const Color(0xFF0F172A),
        appBar: AppBar(
          backgroundColor: const Color(0xFF1E293B),
          elevation: 0,
          title: Row(
            children: [
              const Icon(Icons.bug_report, color: Colors.pinkAccent),
              const SizedBox(width: 12),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: const [
                  Text("Rapports de Bugs", style: TextStyle(fontWeight: FontWeight.bold)),
                  Text("Administration", style: TextStyle(fontSize: 12, color: Colors.grey)),
                ],
              ),
            ],
          ),
          actions: [
             IconButton(
               icon: const Icon(Icons.refresh),
               onPressed: () => _loadData(refresh: true),
             ),
          ],
        ),
        body: Column(
          children: [
            // Filter Bar
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              color: const Color(0xFF1E293B),
              child: SizedBox(
                height: 40,
                child: ListView(
                  scrollDirection: Axis.horizontal,
                  children: [
                    _buildFilterChip('all', 'Tous'),
                    const SizedBox(width: 8),
                    _buildFilterChip('nouveau', 'Nouveaux', color: Colors.pinkAccent),
                    const SizedBox(width: 8),
                    _buildFilterChip('en_cours', 'En cours', color: Colors.cyanAccent),
                    const SizedBox(width: 8),
                    _buildFilterChip('resolu', 'Résolus', color: Colors.greenAccent),
                  ],
                ),
              ),
            ),
            
            // Content
            Expanded(
              child: _bugs.isEmpty && !_isLoading
                  ? const Center(child: Text('Aucun bug trouvé', style: TextStyle(color: Colors.grey)))
                  : ListView.builder(
                      controller: _scrollController,
                      padding: const EdgeInsets.all(24),
                      itemCount: _bugs.length + (_isLoading ? 1 : 0),
                      itemBuilder: (context, index) {
                        if (index == _bugs.length) {
                          return const Center(child: Padding(padding: EdgeInsets.all(16), child: CircularProgressIndicator()));
                        }
                         // Pagination could be added here
                        
                        final bug = _bugs[index];
                        return BugCard(
                          bug: bug,
                          onStatusChange: (status) => _updateStatus(int.parse(bug['id'].toString()), status),
                          onDelete: () => _deleteBug(int.parse(bug['id'].toString())),
                        );
                      },
                    ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildFilterChip(String value, String label, {Color? color}) {
    final isSelected = _selectedStatus == value;
    final displayColor = color ?? Colors.blueAccent;
    
    return FilterChip(
      selected: isSelected,
      label: Text(label),
      labelStyle: TextStyle(
        color: isSelected ? Colors.white : Colors.grey[400],
        fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
      ),
      backgroundColor: Colors.white.withOpacity(0.05),
      selectedColor: displayColor.withOpacity(0.2),
      checkmarkColor: displayColor,
      side: BorderSide(
        color: isSelected ? displayColor : Colors.transparent,
      ),
      onSelected: (val) {
        setState(() => _selectedStatus = value);
        _loadData(refresh: true);
      },
    );
  }
}
