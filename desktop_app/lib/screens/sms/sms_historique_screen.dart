import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:intl/intl.dart';
import 'package:geekboard_desktop/config/api_config.dart';
import 'package:geekboard_desktop/widgets/sidebar.dart';

class SmsHistoryScreen extends StatefulWidget {
  const SmsHistoryScreen({super.key});

  @override
  State<SmsHistoryScreen> createState() => _SmsHistoryScreenState();
}

class _SmsHistoryScreenState extends State<SmsHistoryScreen> {
  bool _isLoading = true;
  List<dynamic> _messages = [];
  Map<String, dynamic> _stats = {'total': 0, 'sent': 0, 'failed': 0};
  
  final _searchController = TextEditingController();
  String? _statusFilter;
  int _currentPage = 1;
  int _totalPages = 1;

  @override
  void initState() {
    super.initState();
    _fetchSms();
  }

  Future<void> _fetchSms({int page = 1}) async {
    setState(() => _isLoading = true);
    
    try {
      String query = '${ApiConfig.baseUrl}${ApiConfig.smsListEndpoint}?page=$page&limit=20';
      if (_searchController.text.isNotEmpty) {
        query += '&search=${Uri.encodeComponent(_searchController.text)}';
      }
      if (_statusFilter != null) {
        query += '&status=$_statusFilter';
      }

      final url = Uri.parse(query);
      final response = await http.get(url);

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _messages = data['data'];
            _stats = data['meta']['stats'];
            _currentPage = data['meta']['current_page'];
            _totalPages = data['meta']['total_pages'];
            _isLoading = false;
          });
        }
      } else {
        throw Exception('Failed to load SMS history');
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

  Future<void> _resendSms(dynamic sms) async {
    setState(() => _isLoading = true);
    try {
      final url = Uri.parse('${ApiConfig.baseUrl}${ApiConfig.smsResendEndpoint}');
      final response = await http.post(
        url,
        headers: {'Content-Type': 'application/json'},
        body: json.encode({
          'id': sms['id'],
          'source': sms['source'],
        }),
      );

      final data = json.decode(response.body);
      if (response.statusCode == 200 && data['success']) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('SMS renvoyé successfully'), backgroundColor: Colors.green),
          );
          _fetchSms(page: _currentPage);
        }
      } else {
        throw Exception(data['message'] ?? 'Erreur lors du renvoi');
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
    return Scaffold(
      backgroundColor: const Color(0xFF0F172A),
      body: Row(
        children: [
          const Sidebar(currentRoute: '/sms'),
          Expanded(
            child: Column(
              children: [
                // Header
                Container(
                  padding: const EdgeInsets.all(24),
                  color: const Color(0xFF1E293B),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'Historique SMS',
                            style: TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.bold),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            'Suivi des messages envoyés et journal système',
                            style: TextStyle(color: Colors.white.withOpacity(0.7)),
                          ),
                        ],
                      ),
                      IconButton(
                        onPressed: () => _fetchSms(page: _currentPage),
                        icon: const Icon(Icons.refresh, color: Colors.blue),
                        tooltip: 'Rafraîchir',
                      ),
                    ],
                  ),
                ),

                Expanded(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      children: [
                        // Stats & Filters
                        Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            // Stats Cards
                            Expanded(child: _buildStatCard('Total', _stats['total'].toString(), Colors.blue, Icons.message)),
                            const SizedBox(width: 16),
                            Expanded(child: _buildStatCard('Envoyés', _stats['sent'].toString(), Colors.green, Icons.check_circle)),
                            const SizedBox(width: 16),
                            Expanded(child: _buildStatCard('Échecs', _stats['failed'].toString(), Colors.red, Icons.error)),
                            
                            const SizedBox(width: 24),
                            
                            // Filters
                            Expanded(
                              flex: 2,
                              child: Column(
                                children: [
                                  Row(
                                    children: [
                                      Expanded(
                                        child: TextField(
                                          controller: _searchController,
                                          style: const TextStyle(color: Colors.white),
                                          decoration: InputDecoration(
                                            hintText: 'Rechercher...',
                                            prefixIcon: const Icon(Icons.search, color: Colors.grey),
                                            filled: true,
                                            fillColor: const Color(0xFF1E293B),
                                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                                            contentPadding: const EdgeInsets.symmetric(vertical: 12),
                                          ),
                                          onSubmitted: (_) => _fetchSms(),
                                        ),
                                      ),
                                      const SizedBox(width: 12),
                                      Container(
                                        decoration: BoxDecoration(
                                          color: const Color(0xFF1E293B),
                                          borderRadius: BorderRadius.circular(12),
                                        ),
                                        padding: const EdgeInsets.symmetric(horizontal: 12),
                                        child: DropdownButtonHideUnderline(
                                          child: DropdownButton<String>(
                                            dropdownColor: const Color(0xFF1E293B),
                                            value: _statusFilter,
                                            hint: const Text('Statut', style: TextStyle(color: Colors.grey)),
                                            icon: const Icon(Icons.filter_list, color: Colors.grey),
                                            items: const [
                                              DropdownMenuItem(value: null, child: Text('Tous', style: TextStyle(color: Colors.white))),
                                              DropdownMenuItem(value: 'sent', child: Text('Envoyés', style: TextStyle(color: Colors.green))),
                                              DropdownMenuItem(value: 'failed', child: Text('Échecs', style: TextStyle(color: Colors.red))),
                                            ],
                                            onChanged: (v) {
                                              setState(() => _statusFilter = v);
                                              _fetchSms();
                                            },
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                        
                        const SizedBox(height: 24),

                        // List
                        Expanded(
                          child: _isLoading 
                              ? const Center(child: CircularProgressIndicator())
                              : _messages.isEmpty
                                  ? Center(child: Text('Aucun message trouvé', style: TextStyle(color: Colors.white.withOpacity(0.5))))
                                  : Column(
                                      children: [
                                        Expanded(
                                          child: ListView.separated(
                                            itemCount: _messages.length,
                                            separatorBuilder: (_, __) => const SizedBox(height: 12),
                                            itemBuilder: (context, index) => _buildMessageCard(_messages[index]),
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
                                                  icon: const Icon(Icons.chevron_left, color: Colors.white),
                                                  onPressed: _currentPage > 1 ? () => _fetchSms(page: _currentPage - 1) : null,
                                                ),
                                                Text(
                                                  'Page $_currentPage / $_totalPages',
                                                  style: const TextStyle(color: Colors.white),
                                                ),
                                                IconButton(
                                                  icon: const Icon(Icons.chevron_right, color: Colors.white),
                                                  onPressed: _currentPage < _totalPages ? () => _fetchSms(page: _currentPage + 1) : null,
                                                ),
                                              ],
                                            ),
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

  Widget _buildStatCard(String title, String value, Color color, IconData icon) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF1E293B),
        borderRadius: BorderRadius.circular(16),
        border: Border(left: BorderSide(color: color, width: 4)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(title, style: TextStyle(color: Colors.white.withOpacity(0.5), fontSize: 13, fontWeight: FontWeight.bold)),
              Icon(icon, color: color, size: 20),
            ],
          ),
          const SizedBox(height: 8),
          Text(value, style: TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.bold)),
        ],
      ),
    );
  }

  Widget _buildMessageCard(dynamic msg) {
    final isSent = msg['status'] == 'sent';
    final date = DateTime.parse(msg['date_envoi']);
    final formattedDate = DateFormat('dd/MM/yyyy HH:mm').format(date);
    
    return Container(
      decoration: BoxDecoration(
        color: const Color(0xFF1E293B),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.white.withOpacity(0.05)),
      ),
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: (isSent ? Colors.green : Colors.red).withOpacity(0.1),
                      borderRadius: BorderRadius.circular(6),
                      border: Border.all(color: (isSent ? Colors.green : Colors.red).withOpacity(0.5)),
                    ),
                    child: Text(
                      isSent ? 'Envoyé' : 'Échec',
                      style: TextStyle(color: isSent ? Colors.green : Colors.red, fontSize: 12, fontWeight: FontWeight.bold),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Text(
                    formattedDate,
                    style: TextStyle(color: Colors.white.withOpacity(0.5), fontSize: 13),
                  ),
                ],
              ),
              if (!isSent)
                TextButton.icon(
                  onPressed: () => _resendSms(msg),
                  icon: const Icon(Icons.refresh, size: 16, color: Colors.blue),
                  label: const Text('Renvoyer', style: TextStyle(color: Colors.blue)),
                ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Icon(Icons.phone_android, size: 16, color: Colors.grey),
              const SizedBox(width: 8),
              Text(msg['telephone'] ?? 'N/A', style: const TextStyle(color: Colors.white, fontFamily: 'monospace')),
              const SizedBox(width: 16),
              const Icon(Icons.person, size: 16, color: Colors.grey),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  '${msg['client_prenom'] ?? ''} ${msg['client_nom'] ?? ''}'.trim().isEmpty ? 'Client Inconnu' : '${msg['client_prenom']} ${msg['client_nom']}',
                  style: const TextStyle(color: Colors.white),
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              if (msg['reparation_id'] != null) ...[
                const SizedBox(width: 16),
                const Icon(Icons.devices, size: 16, color: Colors.grey),
                const SizedBox(width: 8),
                Text(
                  '#${msg['reparation_id']} ${msg['marque'] ?? ''} ${msg['modele'] ?? ''}',
                  style: TextStyle(color: Colors.white.withOpacity(0.7)),
                ),
              ],
            ],
          ),
          const SizedBox(height: 12),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.black.withOpacity(0.2),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Text(
              msg['message'] ?? '',
              style: TextStyle(color: Colors.white.withOpacity(0.9), fontSize: 13),
            ),
          ),
        ],
      ),
    );
  }
}

// Alias for backwards compatibility 
typedef SmsHistoriqueScreen = SmsHistoryScreen;
