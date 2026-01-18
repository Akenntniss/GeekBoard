import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../../services/auth_service.dart';
import '../../../../config/api_config.dart';
import '../../../../services/api_service.dart';
import 'package:intl/intl.dart';
import 'package:url_launcher/url_launcher.dart';

class BillingTab extends StatefulWidget {
  const BillingTab({super.key});

  @override
  State<BillingTab> createState() => _BillingTabState();
}

class _BillingTabState extends State<BillingTab> {
  late final ApiService _apiService;
  bool _isLoading = true;
  Map<String, dynamic>? _data;
  String? _error;

  @override
  void initState() {
    super.initState();
    _apiService = context.read<AuthService>().getApiService();
    _loadData();
  }

  Future<void> _loadData() async {
    try {
      final response = await _apiService.get(ApiConfig.settingsBillingEndpoint);
      if (response != null && response['success'] == true) {
        if (mounted) {
          setState(() {
            _data = response['data'];
            _isLoading = false;
          });
        }
      } else {
        throw Exception(response?['message'] ?? 'Erreur de chargement');
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = e.toString();
          _isLoading = false;
        });
      }
    }
  }

  Future<void> _launchUrl(String path) async {
    final url = Uri.parse('${ApiConfig.siteUrl}$path');
    if (!await launchUrl(url)) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Impossible d\'ouvrir $url')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) return const Center(child: CircularProgressIndicator());
    if (_error != null) return Center(child: Text('Erreur: $_error', style: const TextStyle(color: Colors.red)));
    if (_data == null) return const Center(child: Text('Aucune donnée disponible'));

    return SingleChildScrollView(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildSubscriptionCard(),
          const SizedBox(height: 24),
          _buildUsageCard(),
        ],
      ),
    );
  }

  Widget _buildSubscriptionCard() {
    final status = _data!['status'] ?? 'unknown';
    final planName = _data!['plan_name'] ?? 'Inconnu';
    final price = _data!['price'];
    final period = _data!['period'];

    Color statusColor;
    String statusLabel;
    
    switch(status) {
      case 'active': statusColor = Colors.green; statusLabel = 'Actif'; break;
      case 'trial': statusColor = Colors.orange; statusLabel = 'Période d\'essai'; break;
      case 'expired': statusColor = Colors.red; statusLabel = 'Expiré'; break;
      case 'past_due': statusColor = Colors.redAccent; statusLabel = 'Paiement en attente'; break;
      default: statusColor = Colors.grey; statusLabel = status.toString().toUpperCase();
    }

    final isTrial = _data!['trial']?['is_active'] == true;

    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: BorderSide(color: Theme.of(context).dividerColor),
      ),
      child: Padding(
        padding: const EdgeInsets.all(24.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text("Vue d'ensemble", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                  decoration: BoxDecoration(
                    color: statusColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: statusColor.withOpacity(0.5)),
                  ),
                  child: Text(
                    statusLabel,
                    style: TextStyle(color: statusColor, fontWeight: FontWeight.bold, fontSize: 12),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),
            
            Text("Plan Actuel", style: TextStyle(color: Theme.of(context).textTheme.bodySmall?.color, fontSize: 12, fontWeight: FontWeight.bold)),
            const SizedBox(height: 4),
            Text(planName, style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold)),
            Text("${price}€ / $period", style: TextStyle(color: Theme.of(context).textTheme.bodySmall?.color, fontSize: 14)),
            
            if (isTrial) ...[
              const SizedBox(height: 24),
              _buildTrialProgress(),
            ],

            const SizedBox(height: 32),
            Row(
              children: [
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: () => _launchUrl(_data!['management_urls']['manage_plan']),
                    icon: const Icon(Icons.settings),
                    label: const Text("Gérer mon plan"),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Theme.of(context).primaryColor,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    ),
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: () => _launchUrl(_data!['management_urls']['billing_portal']),
                    icon: const Icon(Icons.receipt),
                    label: const Text("Portail facturation"),
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildTrialProgress() {
    final trialData = _data!['trial'];
    final daysRemaining = trialData['days_remaining'] ?? 0;
    final progress = (trialData['progress'] ?? 0).toDouble() / 100.0;
    final endsAt = trialData['ends_at'];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            const Text("Période d'essai", style: TextStyle(fontSize: 14)),
            Text("$daysRemaining jours restants", style: const TextStyle(fontWeight: FontWeight.bold)),
          ],
        ),
        const SizedBox(height: 8),
        LinearProgressIndicator(
          value: progress,
          backgroundColor: Colors.grey[200],
          color: Colors.orange,
          minHeight: 8,
          borderRadius: BorderRadius.circular(4),
        ),
        if (endsAt != null)
          Padding(
            padding: const EdgeInsets.only(top: 8),
            child: Text(
              "Fin le ${DateFormat('dd/MM/yyyy').format(DateTime.parse(endsAt))}",
              style: TextStyle(fontSize: 12, color: Theme.of(context).textTheme.bodySmall?.color),
            ),
          ),
      ],
    );
  }

  Widget _buildUsageCard() {
    final usage = _data!['usage'];

    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: BorderSide(color: Theme.of(context).dividerColor),
      ),
      child: Padding(
        padding: const EdgeInsets.all(24.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text("Utilisation", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            const SizedBox(height: 24),
            Row(
              children: [
                Expanded(child: _buildUsageStat("SMS Envoyés", "${usage['sms_count']}", Colors.blue)),
                const SizedBox(width: 16),
                Expanded(child: _buildUsageStat("Clients", "${usage['client_count']}", Colors.green)),
              ],
            ),
            const SizedBox(height: 24),
             Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.blue.withOpacity(0.1),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: Colors.blue.withOpacity(0.3)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Row(
                    children: [
                      Icon(Icons.info_outline, color: Colors.blue, size: 20),
                      SizedBox(width: 8),
                      Text("Besoin d'aide ?", style: TextStyle(fontWeight: FontWeight.bold, color: Colors.blue)),
                    ],
                  ),
                  const SizedBox(height: 8),
                  const Text("Notre équipe support est disponible pour vous aider avec votre abonnement."),
                  const SizedBox(height: 12),
                  OutlinedButton.icon(
                    onPressed: () => _launchUrl('mailto:support@servo.tools'),
                    icon: const Icon(Icons.email, size: 16),
                    label: const Text("Contacter le support"),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: Colors.blue,
                      side: const BorderSide(color: Colors.blue),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildUsageStat(String label, String value, Color color) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Theme.of(context).hoverColor,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        children: [
          Text(value, style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: color)),
          const SizedBox(height: 4),
          Text(label, style: TextStyle(color: Theme.of(context).textTheme.bodySmall?.color, fontSize: 12)),
        ],
      ),
    );
  }
}
