/// Daily Statistics Detail Dialog - Enhanced with Charts
import 'dart:ui' as ui;
import 'package:flutter/material.dart';
import 'package:flutter/gestures.dart';
import 'package:intl/intl.dart';
import '../services/api_service.dart';
import '../theme/macos_theme.dart';
import 'reparations/repair_detail_modal.dart';

class DailyStatsDialog extends StatefulWidget {
  final ApiService apiService;
  final String initialTab;
  final Map<String, dynamic> dailyStats;

  const DailyStatsDialog({
    Key? key,
    required this.apiService,
    required this.initialTab,
    required this.dailyStats,
  }) : super(key: key);

  @override
  State<DailyStatsDialog> createState() => _DailyStatsDialogState();
}

class _DailyStatsDialogState extends State<DailyStatsDialog> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  bool _isLoading = true;
  String? _error;
  int _selectedPeriod = 7;
  int _periodOffset = 0; // 0 = current, -1 = previous period, etc.
  bool _canGoNext = false;
  String _periodStart = '';
  String _periodEnd = '';
  
  List<dynamic> _nouvelles = [];
  List<dynamic> _effectuees = [];
  List<dynamic> _restituees = [];
  List<dynamic> _devis = [];
  List<dynamic> _history = [];
  Map<String, dynamic> _summary = {};
  List<int> _hourly = [];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _loadDailyDetails();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _loadDailyDetails() async {
    setState(() => _isLoading = true);
    
    try {
      final response = await widget.apiService.get('/reparations/daily_details.php?period=$_selectedPeriod&offset=$_periodOffset');
      
      if (mounted) {
        setState(() {
          _nouvelles = response['nouvelles'] ?? [];
          _effectuees = response['effectuees'] ?? [];
          _restituees = response['restituees'] ?? [];
          _devis = response['devis'] ?? [];
          _history = response['history'] ?? [];
          _summary = response['summary'] ?? {};
          _hourly = List<int>.from(response['hourly'] ?? []);
          _canGoNext = response['can_go_next'] ?? false;
          _periodStart = response['period_start'] ?? '';
          _periodEnd = response['period_end'] ?? '';
          _isLoading = false;
        });
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

  void _goToPreviousPeriod() {
    setState(() => _periodOffset--);
    _loadDailyDetails();
  }

  void _goToNextPeriod() {
    if (_canGoNext) {
      setState(() => _periodOffset++);
      _loadDailyDetails();
    }
  }

  void _goToCurrentPeriod() {
    setState(() => _periodOffset = 0);
    _loadDailyDetails();
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    return Dialog(
      backgroundColor: Colors.transparent,
      insetPadding: const EdgeInsets.symmetric(horizontal: 24, vertical: 24),
      child: Container(
        width: 950,
        height: 700,
        decoration: BoxDecoration(
          color: isDark ? MacOSTheme.gray800 : Colors.white,
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.3),
              blurRadius: 30,
              offset: const Offset(0, 10),
            ),
          ],
        ),
        child: Column(
          children: [
            _buildHeader(isDark),
            _buildTabs(isDark),
            Expanded(
              child: _isLoading
                  ? const Center(child: CircularProgressIndicator())
                  : _error != null
                      ? _buildErrorView()
                      : TabBarView(
                          controller: _tabController,
                          children: [
                            _buildOverviewTab(isDark),
                            _buildDetailsTab(isDark),
                          ],
                        ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildHeader(bool isDark) {
    // Format period display
    String periodDisplay = _periodOffset == 0 
        ? "Période actuelle" 
        : _periodStart.isNotEmpty && _periodEnd.isNotEmpty
            ? "${_periodStart.substring(8, 10)}/${_periodStart.substring(5, 7)} - ${_periodEnd.substring(8, 10)}/${_periodEnd.substring(5, 7)}"
            : "Période $_periodOffset";
    
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: MacOSTheme.primaryGradient,
        borderRadius: const BorderRadius.only(
          topLeft: Radius.circular(16),
          topRight: Radius.circular(16),
        ),
      ),
      child: Row(
        children: [
          const Icon(Icons.analytics, color: Colors.white, size: 32),
          const SizedBox(width: 16),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                "Statistiques & Activité",
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 22,
                  fontWeight: FontWeight.bold,
                ),
              ),
              Text(
                _periodOffset == 0 
                    ? "Aujourd'hui - ${DateFormat('dd/MM/yyyy').format(DateTime.now())}"
                    : periodDisplay,
                style: TextStyle(
                  color: Colors.white.withOpacity(0.8),
                  fontSize: 13,
                ),
              ),
            ],
          ),
          const Spacer(),
          
          // Navigation arrows + Period selector
          Container(
            decoration: BoxDecoration(
              color: Colors.white.withOpacity(0.15),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                // Left arrow (previous period)
                IconButton(
                  onPressed: _goToPreviousPeriod,
                  icon: const Icon(Icons.chevron_left, color: Colors.white),
                  tooltip: "Période précédente",
                  splashRadius: 20,
                ),
                
                // Period selector
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(4),
                  ),
                  child: DropdownButtonHideUnderline(
                    child: DropdownButton<int>(
                      value: _selectedPeriod,
                      dropdownColor: isDark ? MacOSTheme.gray800 : Colors.white,
                      style: const TextStyle(color: Colors.white),
                      icon: const Icon(Icons.keyboard_arrow_down, color: Colors.white, size: 16),
                      isDense: true,
                      items: [
                        DropdownMenuItem(value: 1, child: Text("24h", style: TextStyle(color: isDark ? Colors.white : Colors.black))),
                        DropdownMenuItem(value: 7, child: Text("7j", style: TextStyle(color: isDark ? Colors.white : Colors.black))),
                        DropdownMenuItem(value: 14, child: Text("14j", style: TextStyle(color: isDark ? Colors.white : Colors.black))),
                        DropdownMenuItem(value: 30, child: Text("30j", style: TextStyle(color: isDark ? Colors.white : Colors.black))),
                      ],
                      onChanged: (val) {
                        if (val != null) {
                          setState(() {
                            _selectedPeriod = val;
                            _periodOffset = 0; // Reset to current when changing period
                          });
                          _loadDailyDetails();
                        }
                      },
                    ),
                  ),
                ),
                
                // Right arrow (next period) - disabled if at current
                IconButton(
                  onPressed: _canGoNext ? _goToNextPeriod : null,
                  icon: Icon(
                    Icons.chevron_right, 
                    color: _canGoNext ? Colors.white : Colors.white38,
                  ),
                  tooltip: _canGoNext ? "Période suivante" : "Période actuelle",
                  splashRadius: 20,
                ),
              ],
            ),
          ),
          
          // Reset to today button (only shown when not on current period)
          if (_periodOffset != 0) ...[
            const SizedBox(width: 8),
            IconButton(
              onPressed: _goToCurrentPeriod,
              icon: const Icon(Icons.today, color: Colors.white),
              tooltip: "Revenir à aujourd'hui",
              splashRadius: 20,
            ),
          ],
          
          const SizedBox(width: 8),
          IconButton(
            onPressed: () => Navigator.pop(context),
            icon: const Icon(Icons.close, color: Colors.white),
          ),
        ],
      ),
    );
  }

  Widget _buildTabs(bool isDark) {
    return Container(
      color: isDark ? const Color(0xFF2C2C2E) : const Color(0xFFF5F5F7),
      child: TabBar(
        controller: _tabController,
        indicatorColor: MacOSTheme.accentBlue,
        labelColor: isDark ? Colors.white : MacOSTheme.accentBlue,
        unselectedLabelColor: Colors.grey,
        tabs: const [
          Tab(
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(Icons.dashboard, size: 18),
                SizedBox(width: 8),
                Text("Vue d'ensemble"),
              ],
            ),
          ),
          Tab(
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(Icons.list_alt, size: 18),
                SizedBox(width: 8),
                Text("Détails du jour"),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildErrorView() {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Icon(Icons.error_outline, color: Colors.red, size: 48),
          const SizedBox(height: 12),
          Text(_error!, style: const TextStyle(color: Colors.red)),
          const SizedBox(height: 12),
          ElevatedButton.icon(
            onPressed: _loadDailyDetails,
            icon: const Icon(Icons.refresh),
            label: const Text("Réessayer"),
          ),
        ],
      ),
    );
  }

  Widget _buildOverviewTab(bool isDark) {
    final caJour = (_summary['ca_jour'] ?? 0).toDouble();
    final caPeriode = (_summary['ca_periode'] ?? 0).toDouble();
    final caMoyenJour = (_summary['ca_moyen_jour'] ?? 0).toDouble();
    
    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // KPI Cards Row - Réparations
          Row(
            children: [
              _buildKpiCard("Aujourd'hui", _nouvelles.length.toString(), Icons.fiber_new, MacOSTheme.accentBlue, "nouvelles réceptions", isDark),
              const SizedBox(width: 16),
              _buildKpiCard("Effectuées", _effectuees.length.toString(), Icons.check_circle, MacOSTheme.successGreen, "réparations terminées", isDark),
              const SizedBox(width: 16),
              _buildKpiCard("Restituées", _restituees.length.toString(), Icons.assignment_return, Colors.teal, "appareils rendus", isDark),
              const SizedBox(width: 16),
              _buildKpiCard("Taux réussite", "${_summary['taux_reussite'] ?? 100}%", Icons.trending_up, MacOSTheme.accentPurple, "sur la période", isDark),
            ],
          ),
          const SizedBox(height: 16),
          
          // KPI Cards Row - Chiffre d'Affaires
          Row(
            children: [
              _buildKpiCard(
                "CA du jour", 
                "${caJour.toStringAsFixed(0)} €", 
                Icons.euro, 
                Colors.amber.shade700, 
                "restitutions du jour", 
                isDark,
              ),
              const SizedBox(width: 16),
              _buildKpiCard(
                "CA période", 
                "${caPeriode.toStringAsFixed(0)} €", 
                Icons.account_balance_wallet, 
                Colors.orange, 
                "$_selectedPeriod derniers jours", 
                isDark,
              ),
              const SizedBox(width: 16),
              _buildKpiCard(
                "CA moyen/jour", 
                "${caMoyenJour.toStringAsFixed(0)} €", 
                Icons.show_chart, 
                Colors.deepOrange, 
                "moyenne sur période", 
                isDark,
              ),
              const SizedBox(width: 16),
              _buildKpiCard(
                "En cours", 
                "${_summary['total_en_cours'] ?? 0}", 
                Icons.pending_actions, 
                Colors.blueGrey, 
                "réparations actives", 
                isDark,
              ),
            ],
          ),
          const SizedBox(height: 24),
          
          // Charts Row
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Evolution Chart
              Expanded(
                flex: 2,
                child: _buildEvolutionChart(isDark),
              ),
              const SizedBox(width: 16),
              // Summary Stats
              Expanded(
                flex: 1,
                child: _buildSummaryCard(isDark),
              ),
            ],
          ),
          const SizedBox(height: 24),
          
          // Hourly Distribution
          _buildHourlyChart(isDark),
        ],
      ),
    );
  }

  Widget _buildKpiCard(String title, String value, IconData icon, Color color, String subtitle, bool isDark) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: isDark ? Colors.white.withOpacity(0.05) : Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: color.withOpacity(0.3)),
          boxShadow: [
            BoxShadow(
              color: color.withOpacity(0.1),
              blurRadius: 10,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: color.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Icon(icon, color: color, size: 20),
                ),
                const Spacer(),
                Text(
                  value,
                  style: TextStyle(
                    fontSize: 28,
                    fontWeight: FontWeight.bold,
                    color: color,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Text(
              title,
              style: TextStyle(
                fontWeight: FontWeight.w600,
                color: isDark ? Colors.white : Colors.black87,
              ),
            ),
            Text(
              subtitle,
              style: TextStyle(
                fontSize: 11,
                color: isDark ? Colors.white54 : Colors.grey[600],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildEvolutionChart(bool isDark) {
    if (_history.isEmpty) {
      return Container(
        height: 280,
        decoration: BoxDecoration(
          color: isDark ? Colors.white.withOpacity(0.05) : Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: isDark ? Colors.white12 : Colors.grey.shade200),
        ),
        child: const Center(child: Text("Aucune donnée")),
      );
    }

    return _InteractiveChart(
      history: _history,
      isDark: isDark,
      selectedPeriod: _selectedPeriod,
    );
  }

  Widget _buildLegendItem(String label, Color color) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 12,
          height: 12,
          decoration: BoxDecoration(
            color: color,
            borderRadius: BorderRadius.circular(3),
          ),
        ),
        const SizedBox(width: 4),
        Text(label, style: TextStyle(fontSize: 11, color: Colors.grey[600])),
      ],
    );
  }

  Widget _buildSummaryCard(bool isDark) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDark ? Colors.white.withOpacity(0.05) : Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: isDark ? Colors.white12 : Colors.grey.shade200),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            "Résumé",
            style: TextStyle(
              fontWeight: FontWeight.bold,
              fontSize: 16,
              color: isDark ? Colors.white : Colors.black87,
            ),
          ),
          const SizedBox(height: 16),
          _buildSummaryItem(
            "Total en cours",
            "${_summary['total_en_cours'] ?? 0}",
            Icons.pending_actions,
            MacOSTheme.warningOrange,
            isDark,
          ),
          const SizedBox(height: 12),
          _buildSummaryItem(
            "Moyenne/jour",
            "${_summary['moyenne_jour'] ?? 0}",
            Icons.show_chart,
            MacOSTheme.accentBlue,
            isDark,
          ),
          const SizedBox(height: 12),
          _buildSummaryItem(
            "Taux réussite",
            "${_summary['taux_reussite'] ?? 100}%",
            Icons.verified,
            MacOSTheme.successGreen,
            isDark,
          ),
        ],
      ),
    );
  }

  Widget _buildSummaryItem(String label, String value, IconData icon, Color color, bool isDark) {
    return Row(
      children: [
        Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: color.withOpacity(0.1),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Icon(icon, color: color, size: 16),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Text(
            label,
            style: TextStyle(
              color: isDark ? Colors.white70 : Colors.grey[700],
              fontSize: 13,
            ),
          ),
        ),
        Text(
          value,
          style: TextStyle(
            fontWeight: FontWeight.bold,
            color: isDark ? Colors.white : Colors.black87,
          ),
        ),
      ],
    );
  }

  Widget _buildHourlyChart(bool isDark) {
    final hours = ["9h", "10h", "11h", "12h", "13h", "14h", "15h", "16h", "17h", "18h", "19h"];
    final maxHourly = _hourly.isEmpty ? 1 : _hourly.reduce((a, b) => a > b ? a : b).clamp(1, 100);

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDark ? Colors.white.withOpacity(0.05) : Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: isDark ? Colors.white12 : Colors.grey.shade200),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            "Répartition par heure (aujourd'hui)",
            style: TextStyle(
              fontWeight: FontWeight.bold,
              fontSize: 16,
              color: isDark ? Colors.white : Colors.black87,
            ),
          ),
          const SizedBox(height: 16),
          SizedBox(
            height: 100,
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: List.generate(_hourly.length.clamp(0, 11), (i) {
                final value = i < _hourly.length ? _hourly[i] : 0;
                final height = maxHourly > 0 ? (value / maxHourly) * 80 : 0.0;
                return Expanded(
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 2),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.end,
                      children: [
                        if (value > 0)
                          Text(
                            value.toString(),
                            style: TextStyle(
                              fontSize: 10,
                              color: isDark ? Colors.white70 : Colors.grey[600],
                            ),
                          ),
                        const SizedBox(height: 4),
                        Container(
                          height: height.clamp(4.0, 80.0),
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              begin: Alignment.bottomCenter,
                              end: Alignment.topCenter,
                              colors: [MacOSTheme.accentBlue, MacOSTheme.accentBlue.withOpacity(0.6)],
                            ),
                            borderRadius: BorderRadius.circular(4),
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          i < hours.length ? hours[i] : '',
                          style: TextStyle(
                            fontSize: 9,
                            color: isDark ? Colors.white54 : Colors.grey[500],
                          ),
                        ),
                      ],
                    ),
                  ),
                );
              }),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDetailsTab(bool isDark) {
    return DefaultTabController(
      length: 4,
      child: Column(
        children: [
          Container(
            color: isDark ? Colors.black12 : Colors.grey.shade100,
            child: TabBar(
              indicatorColor: MacOSTheme.accentBlue,
              labelColor: isDark ? Colors.white : MacOSTheme.accentBlue,
              unselectedLabelColor: Colors.grey,
              tabs: [
                Tab(text: "Nouvelles (${_nouvelles.length})"),
                Tab(text: "Effectuées (${_effectuees.length})"),
                Tab(text: "Restituées (${_restituees.length})"),
                Tab(text: "Devis (${_devis.length})"),
              ],
            ),
          ),
          Expanded(
            child: TabBarView(
              children: [
                _buildRepairList(_nouvelles, MacOSTheme.accentBlue, "Aucune nouvelle réparation"),
                _buildRepairList(_effectuees, MacOSTheme.successGreen, "Aucune réparation effectuée"),
                _buildRepairList(_restituees, Colors.teal, "Aucune restitution"),
                _buildDevisList(_devis),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildRepairList(List<dynamic> items, Color accentColor, String emptyMessage) {
    if (items.isEmpty) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.inbox, size: 48, color: Colors.grey.withOpacity(0.5)),
            const SizedBox(height: 12),
            Text(emptyMessage, style: TextStyle(color: Colors.grey[600])),
          ],
        ),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(12),
      itemCount: items.length,
      itemBuilder: (ctx, i) {
        final item = items[i] as Map<String, dynamic>;
        return _buildRepairTile(item, accentColor);
      },
    );
  }

  Widget _buildRepairTile(Map<String, dynamic> item, Color accentColor) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      color: isDark ? Colors.white.withOpacity(0.05) : Colors.white,
      child: ListTile(
        onTap: () {
          showDialog(
            context: context,
            builder: (_) => RepairDetailModal(
              repair: item,
              apiService: widget.apiService,
              onUpdate: _loadDailyDetails,
            ),
          );
        },
        leading: Container(
          width: 50,
          height: 50,
          decoration: BoxDecoration(
            color: accentColor.withOpacity(0.1),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Center(
            child: Text(
              "#${item['id']}",
              style: TextStyle(
                color: accentColor,
                fontWeight: FontWeight.bold,
                fontSize: 12,
              ),
            ),
          ),
        ),
        title: Text(
          "${item['client_nom'] ?? ''} ${item['client_prenom'] ?? ''}".trim(),
          style: TextStyle(
            fontWeight: FontWeight.w600,
            color: isDark ? Colors.white : Colors.black87,
          ),
        ),
        subtitle: Text(
          item['modele'] ?? '-',
          style: TextStyle(color: Colors.grey[600]),
        ),
        trailing: const Icon(Icons.chevron_right),
      ),
    );
  }

  Widget _buildDevisList(List<dynamic> items) {
    if (items.isEmpty) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.description_outlined, size: 48, color: Colors.grey.withOpacity(0.5)),
            const SizedBox(height: 12),
            Text("Aucun devis", style: TextStyle(color: Colors.grey[600])),
          ],
        ),
      );
    }

    final isDark = Theme.of(context).brightness == Brightness.dark;
    return ListView.builder(
      padding: const EdgeInsets.all(12),
      itemCount: items.length,
      itemBuilder: (ctx, i) {
        final item = items[i] as Map<String, dynamic>;
        return Card(
          margin: const EdgeInsets.only(bottom: 8),
          color: isDark ? Colors.white.withOpacity(0.05) : Colors.white,
          child: ListTile(
            leading: Container(
              width: 50,
              height: 50,
              decoration: BoxDecoration(
                color: MacOSTheme.accentPurple.withOpacity(0.1),
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Icon(Icons.description, color: MacOSTheme.accentPurple),
            ),
            title: Text(item['titre'] ?? 'Devis', style: TextStyle(fontWeight: FontWeight.w600, color: isDark ? Colors.white : Colors.black87)),
            subtitle: Text("${item['client_nom'] ?? ''} ${item['client_prenom'] ?? ''}"),
            trailing: Text("${item['total_ttc'] ?? '0'} €", style: const TextStyle(fontWeight: FontWeight.bold, color: MacOSTheme.successGreen)),
          ),
        );
      },
    );
  }
}

// Custom Chart Painter for Evolution Graph
class _ChartPainter extends CustomPainter {
  final List<dynamic> history;
  final int maxValue;
  final bool isDark;
  final int? hoveredIndex;

  _ChartPainter({
    required this.history, 
    required this.maxValue, 
    required this.isDark,
    this.hoveredIndex,
  });

  @override
  void paint(Canvas canvas, Size size) {
    if (history.isEmpty) return;

    final width = size.width;
    final height = size.height - 20; // Leave space for labels
    final stepX = width / (history.length - 1).clamp(1, 100);

    // Draw grid lines
    final gridPaint = Paint()
      ..color = (isDark ? Colors.white : Colors.grey).withOpacity(0.1)
      ..strokeWidth = 1;

    for (int i = 0; i <= 4; i++) {
      final y = height - (height * i / 4);
      canvas.drawLine(Offset(0, y), Offset(width, y), gridPaint);
    }

    // Draw lines for each series
    _drawLine(canvas, size, history, 'nouvelles', MacOSTheme.accentBlue, stepX, height);
    _drawLine(canvas, size, history, 'effectuees', MacOSTheme.successGreen, stepX, height);
    _drawLine(canvas, size, history, 'restituees', Colors.teal, stepX, height);

    // Draw x-axis labels
    final textStyle = TextStyle(fontSize: 9, color: isDark ? Colors.white54 : Colors.grey);
    for (int i = 0; i < history.length; i++) {
      final x = i * stepX;
      final label = history[i]['label'] ?? '';
      final textSpan = TextSpan(text: label, style: textStyle);
      final textPainter = TextPainter(text: textSpan, textDirection: ui.TextDirection.ltr);
      textPainter.layout();
      textPainter.paint(canvas, Offset(x - textPainter.width / 2, height + 5));
    }
    
    // Draw hover indicator if applicable
    if (hoveredIndex != null && hoveredIndex! >= 0 && hoveredIndex! < history.length) {
      final x = hoveredIndex! * stepX;
      final indicatorPaint = Paint()
        ..color = (isDark ? Colors.white : Colors.black).withOpacity(0.1)
        ..strokeWidth = 1;
      canvas.drawLine(Offset(x, 0), Offset(x, height), indicatorPaint);
    }
  }

  void _drawLine(Canvas canvas, Size size, List<dynamic> data, String key, Color color, double stepX, double height) {
    if (data.isEmpty || maxValue == 0) return;

    final linePaint = Paint()
      ..color = color
      ..strokeWidth = 2.5
      ..style = PaintingStyle.stroke
      ..strokeCap = StrokeCap.round;

    final dotPaint = Paint()
      ..color = color
      ..style = PaintingStyle.fill;

    final path = Path();
    bool started = false;

    for (int i = 0; i < data.length; i++) {
      final value = (data[i][key] ?? 0) as int;
      final x = i * stepX;
      final y = height - (value / maxValue * height);

      if (!started) {
        path.moveTo(x, y);
        started = true;
      } else {
        path.lineTo(x, y);
      }

      // Draw dot - larger if hovered
      final isHovered = hoveredIndex == i;
      canvas.drawCircle(Offset(x, y), isHovered ? 6 : 4, dotPaint);
      
      if (isHovered) {
        // Draw white border on hovered dot
        final borderPaint = Paint()
          ..color = Colors.white
          ..strokeWidth = 2
          ..style = PaintingStyle.stroke;
        canvas.drawCircle(Offset(x, y), 6, borderPaint);
      }
    }

    canvas.drawPath(path, linePaint);
  }

  @override
  bool shouldRepaint(covariant _ChartPainter oldDelegate) {
    return oldDelegate.hoveredIndex != hoveredIndex || 
           oldDelegate.history != history ||
           oldDelegate.maxValue != maxValue;
  }
}

// Interactive Chart Widget with zoom and tooltips
class _InteractiveChart extends StatefulWidget {
  final List<dynamic> history;
  final bool isDark;
  final int selectedPeriod;

  const _InteractiveChart({
    required this.history,
    required this.isDark,
    required this.selectedPeriod,
  });

  @override
  State<_InteractiveChart> createState() => _InteractiveChartState();
}

class _InteractiveChartState extends State<_InteractiveChart> {
  int? _hoveredIndex;
  Offset? _tooltipPosition;

  int get _maxValue {
    int maxValue = 1;
    for (var h in widget.history) {
      if ((h['nouvelles'] ?? 0) > maxValue) maxValue = h['nouvelles'];
      if ((h['effectuees'] ?? 0) > maxValue) maxValue = h['effectuees'];
      if ((h['restituees'] ?? 0) > maxValue) maxValue = h['restituees'];
    }
    return ((maxValue / 5).ceil() * 5).clamp(5, 1000);
  }

  void _handleHover(PointerHoverEvent event, BoxConstraints constraints) {
    final chartWidth = constraints.maxWidth - 32; // padding
    final x = event.localPosition.dx - 16; // adjust for padding
    
    if (widget.history.isEmpty) return;
    
    final stepX = chartWidth / (widget.history.length - 1).clamp(1, 100);
    final index = (x / stepX).round().clamp(0, widget.history.length - 1);
    
    setState(() {
      _hoveredIndex = index;
      _tooltipPosition = event.localPosition;
    });
  }

  void _handleExit(PointerExitEvent event) {
    setState(() {
      _hoveredIndex = null;
      _tooltipPosition = null;
    });
  }

  Widget _buildLegendItem(String label, Color color) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 12,
          height: 12,
          decoration: BoxDecoration(
            color: color,
            borderRadius: BorderRadius.circular(3),
          ),
        ),
        const SizedBox(width: 4),
        Text(label, style: TextStyle(fontSize: 11, color: Colors.grey[600])),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: widget.isDark ? Colors.white.withOpacity(0.05) : Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: widget.isDark ? Colors.white12 : Colors.grey.shade200),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  widget.selectedPeriod == 1 
                      ? "Activité du jour"
                      : "Évolution sur ${widget.selectedPeriod} jours",
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 16,
                    color: widget.isDark ? Colors.white : Colors.black87,
                  ),
                ),
              ),
              Wrap(
                spacing: 10,
                children: [
                  _buildLegendItem("Nouvelles", MacOSTheme.accentBlue),
                  _buildLegendItem("Effectuées", MacOSTheme.successGreen),
                  _buildLegendItem("Restituées", Colors.teal),
                  _buildLegendItem("CA", Colors.orange),
                ],
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            "🖱️ Survolez pour voir les valeurs",
            style: TextStyle(fontSize: 10, color: Colors.grey[500]),
          ),
          const SizedBox(height: 12),
          LayoutBuilder(
            builder: (context, constraints) {
              return SizedBox(
                height: 220,
                width: constraints.maxWidth,
                child: Stack(
                  children: [
                    Positioned.fill(
                      child: MouseRegion(
                        onHover: (e) => _handleHover(e, constraints),
                        onExit: _handleExit,
                        cursor: SystemMouseCursors.precise,
                        child: CustomPaint(
                          size: Size(constraints.maxWidth, 200),
                          painter: _ChartPainter(
                            history: widget.history,
                            maxValue: _maxValue,
                            isDark: widget.isDark,
                            hoveredIndex: _hoveredIndex,
                          ),
                        ),
                      ),
                    ),
                    // Tooltip
                    if (_hoveredIndex != null && _tooltipPosition != null)
                      Positioned(
                        left: _tooltipPosition!.dx.clamp(0.0, constraints.maxWidth - 160.0),
                        top: 0,
                        child: _buildTooltip(),
                      ),
                  ],
                ),
              );
            },
          ),
        ],
      ),
    );
  }

  Widget _buildTooltip() {
    if (_hoveredIndex == null || _hoveredIndex! >= widget.history.length) {
      return const SizedBox.shrink();
    }

    final data = widget.history[_hoveredIndex!];
    final date = data['label'] ?? '';
    final nouvelles = data['nouvelles'] ?? 0;
    final effectuees = data['effectuees'] ?? 0;
    final restituees = data['restituees'] ?? 0;

    return Material(
      elevation: 8,
      borderRadius: BorderRadius.circular(8),
      color: widget.isDark ? const Color(0xFF2C2C2E) : Colors.white,
      child: Container(
        padding: const EdgeInsets.all(12),
        constraints: const BoxConstraints(minWidth: 140),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(8),
          border: Border.all(
            color: widget.isDark ? Colors.white12 : Colors.grey.shade200,
          ),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              date,
              style: TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 13,
                color: widget.isDark ? Colors.white : Colors.black87,
              ),
            ),
            const SizedBox(height: 8),
            _buildTooltipRow("Nouvelles", nouvelles, MacOSTheme.accentBlue),
            const SizedBox(height: 4),
            _buildTooltipRow("Effectuées", effectuees, MacOSTheme.successGreen),
            const SizedBox(height: 4),
            _buildTooltipRow("Restituées", restituees, Colors.teal),
            const SizedBox(height: 6),
            Divider(height: 1, color: widget.isDark ? Colors.white24 : Colors.grey.shade300),
            const SizedBox(height: 6),
            _buildTooltipRowCA("CA", (data['ca'] ?? 0).toDouble(), Colors.orange),
          ],
        ),
      ),
    );
  }

  Widget _buildTooltipRow(String label, int value, Color color) {
    return SizedBox(
      width: 130,
      child: Row(
        children: [
          Container(
            width: 8,
            height: 8,
            decoration: BoxDecoration(
              color: color,
              shape: BoxShape.circle,
            ),
          ),
          const SizedBox(width: 6),
          Expanded(
            child: Text(
              label,
              style: TextStyle(
                fontSize: 11,
                color: widget.isDark ? Colors.white70 : Colors.grey[600],
              ),
            ),
          ),
          Text(
            value.toString(),
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.bold,
              color: color,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTooltipRowCA(String label, double value, Color color) {
    return SizedBox(
      width: 130,
      child: Row(
        children: [
          Container(
            width: 8,
            height: 8,
            decoration: BoxDecoration(
              color: color,
              shape: BoxShape.circle,
            ),
          ),
          const SizedBox(width: 6),
          Expanded(
            child: Text(
              label,
              style: TextStyle(
                fontSize: 11,
                color: widget.isDark ? Colors.white70 : Colors.grey[600],
              ),
            ),
          ),
          Text(
            "${value.toStringAsFixed(0)} €",
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.bold,
              color: color,
            ),
          ),
        ],
      ),
    );
  }
}
