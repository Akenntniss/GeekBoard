import 'package:flutter/material.dart';
import 'package:geekboard_desktop/config/api_config.dart';
import '../../../theme/macos_theme.dart';
import '../../../services/api_service.dart';
import '../../reparations/repair_detail_modal.dart';

import 'package:intl/intl.dart';

class ClientHistoryDialog extends StatefulWidget {
  final int clientId;
  final ApiService apiService;

  const ClientHistoryDialog({
    super.key, 
    required this.clientId,
    required this.apiService,
  });

  @override
  State<ClientHistoryDialog> createState() => _ClientHistoryDialogState();
}

class _ClientHistoryDialogState extends State<ClientHistoryDialog> {
  bool _isLoading = true;
  Map<String, dynamic>? _clientData;
  List<dynamic> _reparations = [];
  Map<String, dynamic> _stats = {};

  @override
  void initState() {
    super.initState();
    _fetchDetails();
  }

  Future<void> _fetchDetails() async {
    try {
      final response = await widget.apiService.get(
        '${ApiConfig.clientsGetEndpoint}?id=${widget.clientId}',
      );

      if (mounted) {
        if (response['success'] == true) {
          setState(() {
            _clientData = response['data'];
            _reparations = response['reparations'];
            _stats = response['statistiques'];
            _isLoading = false;
          });
        }
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        // Error handling could be improved
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    
    return Dialog(
      backgroundColor: MacOSTheme.gray800,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Container(
        width: 800,
        height: 700,
        child: _isLoading 
            ? const Center(child: CircularProgressIndicator())
            : Column(
                children: [
                  // Header
                  Container(
                    padding: const EdgeInsets.all(24),
                    decoration: const BoxDecoration(
                      color: MacOSTheme.gray900,
                      borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Row(
                          children: [
                            IconButton(
                              icon: const Icon(Icons.arrow_back, color: Colors.white),
                              onPressed: () => Navigator.pop(context),
                            ),
                            const SizedBox(width: 16),
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  '${_clientData?['prenom'] ?? ''} ${_clientData?['nom'] ?? ''}',
                                  style: const TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.bold),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  _clientData?['telephone'] ?? '',
                                  style: TextStyle(color: Colors.white.withOpacity(0.7)),
                                ),
                              ],
                            ),
                          ],
                        ),
                        IconButton(
                          icon: const Icon(Icons.close, color: Colors.white),
                          onPressed: () => Navigator.pop(context),
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
                          // Stats Row
                          Row(
                            children: [
                              _buildStatCard('Réparations', (_stats['total_reparations'] ?? 0).toString(), Icons.build, Colors.blue),
                              const SizedBox(width: 16),
                              _buildStatCard('Total Dépensé', '${_stats['total_depense'] ?? 0} €', Icons.euro, Colors.green),
                              const SizedBox(width: 16),
                              _buildStatCard('Dernière Visite', _stats['derniere_visite'] ?? '-', Icons.calendar_today, Colors.orange),
                            ],
                          ),
                          const SizedBox(height: 24),
                          
                          const Text(
                            'Historique des Réparations',
                            style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold),
                          ),
                          const SizedBox(height: 16),

                          // Repairs List
                          Expanded(
                            child: _reparations.isEmpty
                                ? Center(child: Text('Aucune réparation trouvée', style: TextStyle(color: Colors.white.withOpacity(0.5))))
                                : ListView.separated(
                                    itemCount: _reparations.length,
                                    separatorBuilder: (_, __) => const SizedBox(height: 12),
                                    itemBuilder: (context, index) {
                                      final rep = _reparations[index];
                                      String dateLabel = '-';
                                      if (rep['date_creation'] != null) {
                                        try {
                                          dateLabel = DateFormat('dd/MM').format(DateTime.parse(rep['date_creation']));
                                        } catch (_) {}
                                      }                                      

                                      return InkWell(
                                        onTap: () {
                                          final repairDetails = Map<String, dynamic>.from(rep);
                                          // Inject client info
                                          repairDetails['client_nom'] = _clientData?['nom'];
                                          repairDetails['client_prenom'] = _clientData?['prenom'];
                                          repairDetails['client_telephone'] = _clientData?['telephone'];
                                          // Ensure keys match
                                          repairDetails['statut'] = rep['status'] ?? rep['statut'];
                                          repairDetails['description_probleme'] = rep['probleme'] ?? rep['description_probleme'];
                                          repairDetails['date_reception'] = rep['date_creation'] ?? rep['date_reception'];

                                          showDialog(
                                            context: context,
                                            builder: (_) => RepairDetailModal(
                                              repair: repairDetails,
                                              apiService: widget.apiService,
                                              onUpdate: _fetchDetails,
                                            ),
                                          );
                                        },
                                        borderRadius: BorderRadius.circular(12),
                                        child: Container(
                                          padding: const EdgeInsets.all(16),
                                          decoration: BoxDecoration(
                                            color: MacOSTheme.gray900,
                                            borderRadius: BorderRadius.circular(12),
                                            border: Border.all(color: Colors.white.withOpacity(0.05)),
                                          ),
                                          child: Row(
                                            children: [
                                              Container(
                                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                                                decoration: BoxDecoration(
                                                  color: Colors.blue.withOpacity(0.1),
                                                  borderRadius: BorderRadius.circular(6),
                                                ),
                                                child: Text(
                                                  dateLabel,
                                                  style: const TextStyle(color: Colors.blue, fontWeight: FontWeight.bold),
                                                ),
                                              ),

                                              const SizedBox(width: 16),
                                              Expanded(
                                                child: Column(
                                                  crossAxisAlignment: CrossAxisAlignment.start,
                                                  children: [
                                                    Text(
                                                      '${rep['appareil'] ?? ''} - ${rep['marque'] ?? ''} ${rep['modele'] ?? ''}',
                                                      style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
                                                    ),
                                                    const SizedBox(height: 4),
                                                    Text(
                                                      rep['probleme'] ?? '',
                                                      style: TextStyle(color: Colors.white.withOpacity(0.7), fontSize: 13),
                                                    ),
                                                  ],
                                                ),
                                              ),
                                              const SizedBox(width: 16),
                                              Column(
                                                crossAxisAlignment: CrossAxisAlignment.end,
                                                children: [
                                                  Text(
                                                    '${rep['prix']} €',
                                                    style: const TextStyle(color: Colors.green, fontWeight: FontWeight.bold),
                                                  ),
                                                  const SizedBox(height: 4),
                                                  Text(
                                                    rep['status'] ?? 'N/A',
                                                    style: TextStyle(color: Colors.white.withOpacity(0.5), fontSize: 12),
                                                  ),
                                                ],
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
                ],
              ),
      ),
    );
  }

  Widget _buildStatCard(String title, String value, IconData icon, Color color) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: MacOSTheme.gray900,
          borderRadius: BorderRadius.circular(12),
          border: Border(left: BorderSide(color: color, width: 4)),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(icon, color: color, size: 20),
                const SizedBox(width: 8),
                Text(title, style: TextStyle(color: Colors.white.withOpacity(0.5), fontSize: 12)),
              ],
            ),
            const SizedBox(height: 8),
            Text(value, style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold)),
          ],
        ),
      ),
    );
  }
}
