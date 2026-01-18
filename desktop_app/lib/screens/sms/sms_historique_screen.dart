import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:intl/intl.dart';
import 'package:geekboard_desktop/config/api_config.dart';
import 'package:geekboard_desktop/services/auth_service.dart';
import 'package:geekboard_desktop/widgets/sidebar.dart';
import 'package:provider/provider.dart';
import '../../widgets/app_shell.dart';

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

  Future<void> _fetchSms({int page = 1, bool silent = false}) async {
    if (!silent) setState(() => _isLoading = true);
    
    try {
      final queryParams = <String, String>{
        'page': page.toString(),
        'limit': '20',
      };
      if (_searchController.text.isNotEmpty) {
        queryParams['search'] = _searchController.text;
      }
      if (_statusFilter != null) {
        queryParams['status'] = _statusFilter!;
      }

      final apiService = context.read<AuthService>().getApiService();
      final data = await apiService.get(ApiConfig.smsListEndpoint, queryParams);

      if (mounted) {
        setState(() {
          _messages = data['data'];
          _stats = data['meta']['stats'];
          _currentPage = data['meta']['current_page'];
          _totalPages = data['meta']['total_pages'];
          _isLoading = false;
        });
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
    // Ne pas bloquer l'UI avec un chargement global
    try {
      final apiService = context.read<AuthService>().getApiService();
      await apiService.post(ApiConfig.smsResendEndpoint, {
        'id': sms['id'],
        'source': sms['source'],
      });

      if (mounted) {
        // Mise à jour optimiste locale
        setState(() {
          final index = _messages.indexWhere((m) => m['id'] == sms['id']);
          if (index != -1) {
            _messages[index]['status'] = 'sent';
            // Optionnel : mettre à jour la date si nécessaire
            // _messages[index]['date_envoi'] = DateTime.now().toIso8601String(); 
          }
        });

        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('SMS renvoyé avec succès'), backgroundColor: Colors.green),
        );
        
        // Rafraîchir silencieusement pour obtenir les données réelles du serveur
        _fetchSms(page: _currentPage, silent: true);
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red),
        );
      }
    }
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
      currentRoute: '/sms',
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
                        'Historique SMS',
                        style: TextStyle(color: textColor, fontSize: 24, fontWeight: FontWeight.bold),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Suivi des messages envoyés et journal système',
                        style: TextStyle(color: subTextColor),
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
                        Expanded(child: _buildStatCard('Total', _stats['total'].toString(), Colors.blue, Icons.message, isDark, cardColor, borderColor, textColor, subTextColor)),
                        const SizedBox(width: 16),
                        Expanded(child: _buildStatCard('Envoyés', _stats['sent'].toString(), Colors.green, Icons.check_circle, isDark, cardColor, borderColor, textColor, subTextColor)),
                        const SizedBox(width: 16),
                        Expanded(child: _buildStatCard('Échecs', _stats['failed'].toString(), Colors.red, Icons.error, isDark, cardColor, borderColor, textColor, subTextColor)),
                        
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
                                      style: TextStyle(color: textColor),
                                      decoration: InputDecoration(
                                        hintText: 'Rechercher...',
                                        hintStyle: TextStyle(color: placeholderColor),
                                        prefixIcon: Icon(Icons.search, color: placeholderColor),
                                        filled: true,
                                        fillColor: inputFillColor,
                                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                                        contentPadding: const EdgeInsets.symmetric(vertical: 12),
                                      ),
                                      onSubmitted: (_) => _fetchSms(),
                                    ),
                                  ),
                                  const SizedBox(width: 12),
                                  Container(
                                    decoration: BoxDecoration(
                                      color: inputFillColor,
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    padding: const EdgeInsets.symmetric(horizontal: 12),
                                    child: DropdownButtonHideUnderline(
                                      child: DropdownButton<String>(
                                        dropdownColor: isDark ? const Color(0xFF1E293B) : Colors.white,
                                        value: _statusFilter,
                                        hint: Text('Statut', style: TextStyle(color: subTextColor)),
                                        icon: Icon(Icons.filter_list, color: subTextColor),
                                        items: [
                                          DropdownMenuItem(value: null, child: Text('Tous', style: TextStyle(color: textColor))),
                                          const DropdownMenuItem(value: 'sent', child: Text('Envoyés', style: TextStyle(color: Colors.green))),
                                          const DropdownMenuItem(value: 'failed', child: Text('Échecs', style: TextStyle(color: Colors.red))),
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
                              ? Center(child: Text('Aucun message trouvé', style: TextStyle(color: subTextColor)))
                              : Column(
                                  children: [
                                    Expanded(
                                      child: ListView.separated(
                                        itemCount: _messages.length,
                                        separatorBuilder: (_, __) => const SizedBox(height: 12),
                                        itemBuilder: (context, index) => _buildMessageCard(_messages[index], isDark, cardColor, borderColor, textColor, subTextColor),
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
                                              icon: Icon(Icons.chevron_left, color: textColor),
                                              onPressed: _currentPage > 1 ? () => _fetchSms(page: _currentPage - 1) : null,
                                            ),
                                            Text(
                                              'Page $_currentPage / $_totalPages',
                                              style: TextStyle(color: textColor),
                                            ),
                                            IconButton(
                                              icon: Icon(Icons.chevron_right, color: textColor),
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
    );
  }

  Widget _buildStatCard(String title, String value, Color color, IconData icon, bool isDark, Color cardColor, Color borderColor, Color textColor, Color subTextColor) {
    return Container(
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: borderColor),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(isDark ? 0.2 : 0.05),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(16),
        child: IntrinsicHeight(
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Container(width: 4, color: color),
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(title, style: TextStyle(color: subTextColor, fontSize: 13, fontWeight: FontWeight.bold)),
                          Icon(icon, color: color, size: 20),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Text(value, style: TextStyle(color: textColor, fontSize: 24, fontWeight: FontWeight.bold)),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildMessageCard(dynamic msg, bool isDark, Color cardColor, Color borderColor, Color textColor, Color subTextColor) {
    final isSent = msg['status'] == 'sent';
    DateTime? date;
    try {
      date = DateTime.parse(msg['date_envoi']);
    } catch (_) {
      date = DateTime.now();
    }
    final formattedDate = date != null ? DateFormat('dd/MM/yyyy HH:mm').format(date) : '-';
    
    return Container(
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: borderColor),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(isDark ? 0.1 : 0.05),
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
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
                    style: TextStyle(color: subTextColor, fontSize: 13),
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
              Icon(Icons.phone_android, size: 16, color: subTextColor),
              const SizedBox(width: 8),
              Text(msg['telephone'] ?? 'N/A', style: TextStyle(color: textColor, fontFamily: 'monospace')),
              const SizedBox(width: 16),
              Icon(Icons.person, size: 16, color: subTextColor),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  '${msg['client_prenom'] ?? ''} ${msg['client_nom'] ?? ''}'.trim().isEmpty ? 'Client Inconnu' : '${msg['client_prenom']} ${msg['client_nom']}',
                  style: TextStyle(color: textColor),
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              if (msg['reparation_id'] != null) ...[
                const SizedBox(width: 16),
                Icon(Icons.devices, size: 16, color: subTextColor),
                const SizedBox(width: 8),
                Text(
                  '#${msg['reparation_id']} ${msg['marque'] ?? ''} ${msg['modele'] ?? ''}',
                  style: TextStyle(color: subTextColor),
                ),
              ],
            ],
          ),
          const SizedBox(height: 12),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: isDark ? Colors.black.withOpacity(0.2) : Colors.grey[100],
              borderRadius: BorderRadius.circular(8),
            ),
            child: Text(
              msg['message'] ?? '',
              style: TextStyle(color: isDark ? Colors.white.withOpacity(0.9) : Colors.black87, fontSize: 13),
            ),
          ),
        ],
      ),
    );
  }
}

// Alias for backwards compatibility 
typedef SmsHistoriqueScreen = SmsHistoryScreen;
