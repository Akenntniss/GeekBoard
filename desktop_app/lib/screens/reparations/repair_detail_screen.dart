/// Repair Detail Screen - Détails d'une réparation
import 'package:flutter/material.dart';
import 'package:flutter/cupertino.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../../services/api_service.dart';
import '../../services/auth_service.dart';
import '../../theme/macos_theme.dart';
import '../../config/api_config.dart';

class RepairDetailScreen extends StatefulWidget {
  final int repairId;

  const RepairDetailScreen({super.key, required this.repairId});

  @override
  State<RepairDetailScreen> createState() => _RepairDetailScreenState();
}

class _RepairDetailScreenState extends State<RepairDetailScreen> {
  Map<String, dynamic>? _repair;
  List<Map<String, dynamic>> _logs = [];
  bool _isLoading = true;
  String? _error;

  final List<Map<String, String>> _statuses = [
    {'value': 'en_cours', 'label': 'En cours'},
    {'value': 'diagnostic', 'label': 'Diagnostic'},
    {'value': 'attente_piece', 'label': 'Attente pièce'},
    {'value': 'reparation_en_cours', 'label': 'Réparation en cours'},
    {'value': 'reparation_effectue', 'label': 'Réparation effectuée'},
    {'value': 'restitue', 'label': 'Restitué'},
  ];

  @override
  void initState() {
    super.initState();
    _loadRepairDetails();
  }

  Future<void> _loadRepairDetails() async {
    setState(() => _isLoading = true);
    
    try {
      final authService = context.read<AuthService>();
      final apiService = authService.getApiService();
      
      final response = await apiService.get(
        '${ApiConfig.reparationsGetEndpoint}?id=${widget.repairId}'
      );
      
      setState(() {
        _repair = response['reparation'];
        _logs = List<Map<String, dynamic>>.from(response['reparation']?['logs'] ?? []);
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  Future<void> _updateStatus(String newStatus) async {
    try {
      final authService = context.read<AuthService>();
      final apiService = authService.getApiService();
      
      await apiService.post(ApiConfig.reparationsUpdateEndpoint, {
        'id': widget.repairId,
        'statut': newStatus,
      });
      
      // Reload to get fresh data
      await _loadRepairDetails();
      
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Statut mis à jour avec succès')),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erreur: $e')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F5F7),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        leading: CupertinoButton(
          child: const Icon(CupertinoIcons.back, color: MacOSTheme.accentBlue),
          onPressed: () => Navigator.pop(context),
        ),
        title: Text(
          'Réparation #${widget.repairId}',
          style: const TextStyle(fontWeight: FontWeight.w600),
        ),
      ),
      body: _isLoading
          ? const Center(child: CupertinoActivityIndicator())
          : _error != null
              ? Center(child: Text('Erreur: $_error'))
              : _buildContent(),
    );
  }

  Widget _buildContent() {
    if (_repair == null) return const SizedBox();
    
    final dateFormat = DateFormat('dd/MM/yyyy HH:mm');
    
    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Client info card
          MacOSCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: MacOSTheme.accentBlue.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: const Icon(
                        CupertinoIcons.person_fill,
                        color: MacOSTheme.accentBlue,
                      ),
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            '${_repair!['client_prenom'] ?? ''} ${_repair!['client_nom'] ?? 'Client inconnu'}',
                            style: const TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          if (_repair!['client_telephone'] != null)
                            Text(
                              _repair!['client_telephone'],
                              style: TextStyle(color: Colors.grey[600]),
                            ),
                        ],
                      ),
                    ),
                    StatusBadge(status: _repair!['statut'] ?? ''),
                  ],
                ),
              ],
            ),
          ),
          
          const SizedBox(height: 16),
          
          // Device info card
          MacOSCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Appareil',
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: MacOSTheme.textSecondary,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  _repair!['modele'] ?? 'Non spécifié',
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 16),
                const Text(
                  'Problème',
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: MacOSTheme.textSecondary,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  _repair!['description_probleme'] ?? 'Non décrit',
                  style: const TextStyle(fontSize: 14),
                ),
              ],
            ),
          ),
          
          const SizedBox(height: 16),
          
          // Pricing card
          MacOSCard(
            child: Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Prix estimé',
                        style: TextStyle(
                          fontSize: 12,
                          color: MacOSTheme.textSecondary,
                        ),
                      ),
                      Text(
                        '${_repair!['prix_estime'] ?? '0'} €',
                        style: const TextStyle(
                          fontSize: 24,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ],
                  ),
                ),
                Container(
                  width: 1,
                  height: 40,
                  color: MacOSTheme.divider,
                ),
                Expanded(
                  child: Padding(
                    padding: const EdgeInsets.only(left: 16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Prix final',
                          style: TextStyle(
                            fontSize: 12,
                            color: MacOSTheme.textSecondary,
                          ),
                        ),
                        Text(
                          '${_repair!['prix_final'] ?? '-'} €',
                          style: const TextStyle(
                            fontSize: 24,
                            fontWeight: FontWeight.bold,
                            color: MacOSTheme.successGreen,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
          
          const SizedBox(height: 24),
          
          // Status change
          const Text(
            'Changer le statut',
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 12),
          
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: _statuses.map((status) {
              final isSelected = _repair!['statut'] == status['value'];
              return GestureDetector(
                onTap: isSelected ? null : () => _updateStatus(status['value']!),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                  decoration: BoxDecoration(
                    color: isSelected 
                        ? MacOSTheme.getStatusColor(status['value']!)
                        : Colors.white,
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(
                      color: isSelected 
                          ? MacOSTheme.getStatusColor(status['value']!)
                          : MacOSTheme.divider,
                    ),
                  ),
                  child: Text(
                    status['label']!,
                    style: TextStyle(
                      color: isSelected ? Colors.white : MacOSTheme.textPrimary,
                      fontWeight: isSelected ? FontWeight.w600 : FontWeight.normal,
                    ),
                  ),
                ),
              );
            }).toList(),
          ),
          
          const SizedBox(height: 24),
          
          // Logs
          if (_logs.isNotEmpty) ...[
            const Text(
              'Historique',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
              ),
            ),
            const SizedBox(height: 12),
            ...(_logs.take(5).map((log) => Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: MacOSCard(
                padding: const EdgeInsets.all(12),
                child: Row(
                  children: [
                    Icon(
                      CupertinoIcons.time,
                      size: 16,
                      color: Colors.grey[400],
                    ),
                    const SizedBox(width: 8),
                    Text(
                      log['date_action'] ?? '',
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey[600],
                      ),
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Text(
                        '${log['action_type'] ?? ''}: ${log['old_value'] ?? ''} → ${log['new_value'] ?? ''}',
                        style: const TextStyle(fontSize: 13),
                      ),
                    ),
                  ],
                ),
              ),
            )).toList()),
          ],
        ],
      ),
    );
  }
}
