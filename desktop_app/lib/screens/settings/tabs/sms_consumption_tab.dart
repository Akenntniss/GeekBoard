import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../../services/auth_service.dart';
import '../../../../config/api_config.dart';
import '../../../../services/api_service.dart';
import 'package:fl_chart/fl_chart.dart';
import 'package:intl/intl.dart';

class SmsConsumptionTab extends StatefulWidget {
  const SmsConsumptionTab({super.key});

  @override
  State<SmsConsumptionTab> createState() => _SmsConsumptionTabState();
}

class _SmsConsumptionTabState extends State<SmsConsumptionTab> {
  late final ApiService _apiService;
  bool _isLoading = true;
  Map<String, dynamic>? _data;
  bool _isSaving = false;

  // Controllers for settings form
  late TextEditingController _capAmountController;
  late TextEditingController _alertEmailController;
  bool _hardCapEnabled = false;
  bool _alertsEnabled = false;

  @override
  void initState() {
    super.initState();
    _apiService = context.read<AuthService>().getApiService();
    _capAmountController = TextEditingController();
    _alertEmailController = TextEditingController();
    _loadData();
  }

  @override
  void dispose() {
    _capAmountController.dispose();
    _alertEmailController.dispose();
    super.dispose();
  }

  Future<void> _loadData() async {
    try {
      final response = await _apiService.get(ApiConfig.settingsSmsEndpoint);
      if (response != null && response['success'] == true) {
        final data = response['data'];
        final settings = data['settings'];
        
        if (mounted) {
          setState(() {
            _data = data;
            _hardCapEnabled = settings['hard_cap_enabled'] ?? false;
            _capAmountController.text = (settings['hard_cap_amount'] ?? 20).toString();
            _alertsEnabled = settings['alerts_enabled'] ?? true;
            _alertEmailController.text = settings['alert_email'] ?? '';
            _isLoading = false;
          });
        }
      }
    } catch (e) {
      if (mounted) setState(() => _isLoading = false);
      print("Error loading SMS data: $e");
    }
  }

  Future<void> _saveSettings() async {
    setState(() => _isSaving = true);
    try {
      final payload = {
        'hard_cap_enabled': _hardCapEnabled ? 1 : 0,
        'hard_cap_amount': double.tryParse(_capAmountController.text) ?? 20.0,
        'alerts_enabled': _alertsEnabled ? 1 : 0,
        'alert_email': _alertEmailController.text,
      };

      final response = await _apiService.post(ApiConfig.settingsSmsEndpoint, payload);
      
      if (response != null && response['success'] == true) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Paramètres SMS enregistrés avec succès'), backgroundColor: Colors.green),
        );
      } else {
        throw Exception(response?['message'] ?? 'Erreur inconnue');
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red),
      );
    } finally {
      if (mounted) setState(() => _isSaving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) return const Center(child: CircularProgressIndicator());
    if (_data == null) return const Center(child: Text("Impossible de charger les données SMS."));

    return SingleChildScrollView(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(child: _buildUsageSummaryCard()),
              const SizedBox(width: 24),
              Expanded(child: _buildCostControlCard()),
            ],
          ),
          const SizedBox(height: 24),
          _buildHistoryChart(),
        ],
      ),
    );
  }

  Widget _buildUsageSummaryCard() {
    final usage = _data!['usage'];
    final quota = usage['quota'];
    final settings = _data!['settings'];
    
    final total = double.tryParse(quota['total'].toString()) ?? 0;
    final used = double.tryParse(quota['used'].toString()) ?? 0;
    final percent = total > 0 ? (used / total) : 0.0;
    
    final isUnlimited = quota['is_unlimited'] == true;
    final isTrial = quota['is_trial'] == true;

    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12), side: BorderSide(color: Theme.of(context).dividerColor)),
      child: Padding(
        padding: const EdgeInsets.all(24.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text("Utilisation SMS", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                if (isTrial)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(color: Colors.blue.withOpacity(0.1), borderRadius: BorderRadius.circular(4)),
                    child: const Text("Essai - Illimité", style: TextStyle(color: Colors.blue, fontSize: 12)),
                  )
                else if (isUnlimited)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(color: Colors.green.withOpacity(0.1), borderRadius: BorderRadius.circular(4)),
                    child: const Text("Illimité", style: TextStyle(color: Colors.green, fontSize: 12)),
                  ),
              ],
            ),
            const SizedBox(height: 24),
            
            if (!isUnlimited && !isTrial) ...[
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text("${used.toInt()} / ${total.toInt()} SMS utilisés"),
                  Text("${(percent * 100).toInt()}%", style: const TextStyle(fontWeight: FontWeight.bold)),
                ],
              ),
              const SizedBox(height: 8),
              LinearProgressIndicator(
                value: percent.clamp(0.0, 1.0),
                backgroundColor: Colors.grey[200],
                color: percent > 0.9 ? Colors.red : (percent > 0.8 ? Colors.orange : Colors.green),
                minHeight: 12,
                borderRadius: BorderRadius.circular(6),
              ),
              const SizedBox(height: 24),
            ],

            Row(
              children: [
                Expanded(child: _buildMiniStat(usage['sent_total'].toString(), "Envoyés", Colors.blue)),
                const SizedBox(width: 8),
                Expanded(child: _buildMiniStat(usage['extra_billed'].toString(), "Extra", Colors.orange)),
                const SizedBox(width: 8),
                Expanded(child: _buildMiniStat("${usage['extra_cost']}€", "Coût", Colors.green)),
              ],
            ),
            
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(color: Theme.of(context).hoverColor, borderRadius: BorderRadius.circular(8)),
              child: Center(
                child: Text(
                  "Période: ${DateFormat('dd/MM/yyyy').format(DateTime.parse(usage['period_start']))} - ${DateFormat('dd/MM/yyyy').format(DateTime.parse(usage['period_end']))}",
                  style: TextStyle(fontSize: 12, color: Theme.of(context).textTheme.bodySmall?.color),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildMiniStat(String value, String label, Color color) {
    return Column(
      children: [
        Text(value, style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: color)),
        Text(label, style: const TextStyle(fontSize: 12, color: Colors.grey)),
      ],
    );
  }

  Widget _buildCostControlCard() {
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12), side: BorderSide(color: Theme.of(context).dividerColor)),
      child: Padding(
        padding: const EdgeInsets.all(24.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text("Contrôle des coûts", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            const SizedBox(height: 24),
            
            SwitchListTile(
              title: const Text("Plafond de sécurité", style: TextStyle(fontWeight: FontWeight.bold)),
              subtitle: const Text("Limiter les frais SMS supplémentaires"),
              value: _hardCapEnabled,
              onChanged: (val) => setState(() => _hardCapEnabled = val),
              contentPadding: EdgeInsets.zero,
            ),
            
            if (_hardCapEnabled)
              Padding(
                padding: const EdgeInsets.only(bottom: 16),
                child: TextField(
                  controller: _capAmountController,
                  decoration: const InputDecoration(
                    labelText: "Montant maximum (€)",
                    border: OutlineInputBorder(),
                    prefixIcon: Icon(Icons.euro),
                  ),
                  keyboardType: TextInputType.number,
                ),
              ),
              
            SwitchListTile(
              title: const Text("Alertes par email", style: TextStyle(fontWeight: FontWeight.bold)),
              subtitle: const Text("Recevoir des alertes à 80%, 90% et 100%"),
              value: _alertsEnabled,
              onChanged: (val) => setState(() => _alertsEnabled = val),
              contentPadding: EdgeInsets.zero,
            ),
            
            if (_alertsEnabled)
              Padding(
                padding: const EdgeInsets.only(bottom: 16),
                child: TextField(
                  controller: _alertEmailController,
                  decoration: const InputDecoration(
                    labelText: "Email pour les alertes",
                    border: OutlineInputBorder(),
                    prefixIcon: Icon(Icons.email),
                  ),
                  keyboardType: TextInputType.emailAddress,
                ),
              ),
              
             SizedBox(
               width: double.infinity,
               child: ElevatedButton.icon(
                 onPressed: _isSaving ? null : _saveSettings,
                 icon: _isSaving ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : const Icon(Icons.save),
                 label: const Text("Enregistrer les paramètres SMS"),
                 style: ElevatedButton.styleFrom(
                   backgroundColor: Theme.of(context).primaryColor,
                   foregroundColor: Colors.white,
                   padding: const EdgeInsets.symmetric(vertical: 16),
                 ),
               ),
             ),
          ],
        ),
      ),
    );
  }

  Widget _buildHistoryChart() {
    final history = _data!['history'] as List<dynamic>;
    if (history.isEmpty) return const SizedBox.shrink();

    // Prepare data for FlaChart
    List<BarChartGroupData> barGroups = [];
    double maxY = 0;
    
    // We expect history to have 'month_year' and 'total_sms'
    // Let's take last 12 entries
    
    for (int i = 0; i < history.length; i++) {
      final item = history[i];
      final count = double.tryParse(item['total_sms'].toString()) ?? 0;
      if (count > maxY) maxY = count;
      
      barGroups.add(
        BarChartGroupData(
          x: i,
          barRods: [
            BarChartRodData(
              toY: count,
              color: Theme.of(context).primaryColor,
              width: 16,
              borderRadius: const BorderRadius.vertical(top: Radius.circular(4)),
            )
          ],
        ),
      );
    }
    
    if (maxY == 0) maxY = 100;

    return Card(
      elevation: 0,
       shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12), side: BorderSide(color: Theme.of(context).dividerColor)),
      child: Padding(
        padding: const EdgeInsets.all(24.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text("Historique des 12 derniers mois", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            const SizedBox(height: 24),
            SizedBox(
              height: 200,
              child: BarChart(
                BarChartData(
                  alignment: BarChartAlignment.spaceAround,
                  maxY: maxY * 1.2,
                  barTouchData: BarTouchData(
                    enabled: true,
                    touchTooltipData: BarTouchTooltipData(
                      getTooltipColor: (_) => Colors.blueGrey,
                      getTooltipItem: (group, groupIndex, rod, rodIndex) {
                        return BarTooltipItem(
                          '${rod.toY.toInt()} SMS',
                          const TextStyle(color: Colors.white),
                        );
                      },
                    ),
                  ),
                  titlesData: FlTitlesData(
                    show: true,
                    bottomTitles: AxisTitles(
                      sideTitles: SideTitles(
                        showTitles: true,
                        getTitlesWidget: (double value, TitleMeta meta) {
                          if (value.toInt() >= 0 && value.toInt() < history.length) {
                             final dateStr = history[value.toInt()]['month_year']; // YYYY-MM
                             // Simple format
                             try {
                               final date = DateFormat('yyyy-MM').parse(dateStr);
                               return Padding(
                                 padding: const EdgeInsets.only(top: 8.0),
                                 child: Text(DateFormat('MMM').format(date), style: const TextStyle(fontSize: 10)),
                               );
                             } catch (e) {
                               return const Text('');
                             }
                          }
                          return const Text('');
                        },
                      ),
                    ),
                    leftTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
                    topTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
                    rightTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
                  ),
                  gridData: const FlGridData(show: false),
                  borderData: FlBorderData(show: false),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
