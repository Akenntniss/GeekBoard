import 'package:flutter/material.dart';
import 'package:fl_chart/fl_chart.dart';

// --- KPI Stat Card ---
class KpiStatCard extends StatelessWidget {
  final String label;
  final String value;
  final String subtext;
  final IconData icon;
  final Color color;
  final bool isCurrency;

  const KpiStatCard({
    super.key,
    required this.label,
    required this.value,
    required this.subtext,
    required this.icon,
    required this.color,
    this.isCurrency = false,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: const Color(0xFF1E293B),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.white.withOpacity(0.05)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.2),
            blurRadius: 8,
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
                child: Icon(icon, color: color, size: 18),
              ),
              const SizedBox(width: 12),
              Text(
                label.toUpperCase(),
                style: TextStyle(
                  color: Colors.grey[400],
                  fontSize: 12,
                  fontWeight: FontWeight.bold,
                  letterSpacing: 0.5,
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Text(
            isCurrency ? '$value €' : value,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 28,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            subtext,
            style: TextStyle(
              color: Colors.grey[500],
              fontSize: 13,
            ),
          ),
        ],
      ),
    );
  }
}

// --- KPI Line Chart (Panier Moyen) ---
class KpiLineChart extends StatelessWidget {
  final List<Map<String, dynamic>> data;

  const KpiLineChart({super.key, required this.data});

  @override
  Widget build(BuildContext context) {
    if (data.isEmpty) return const Center(child: Text("Pas de données", style: TextStyle(color: Colors.grey)));

    final spots = data.asMap().entries.map((e) {
      final val = double.tryParse(e.value['panier_moyen'].toString()) ?? 0.0;
      return FlSpot(e.key.toDouble(), val);
    }).toList();

    return LineChart(
      LineChartData(
        gridData: FlGridData(
          show: true,
          drawVerticalLine: false,
          horizontalInterval: 20,
          getDrawingHorizontalLine: (value) => FlLine(
            color: Colors.white.withOpacity(0.05),
            strokeWidth: 1,
          ),
        ),
        titlesData: FlTitlesData(
          show: true,
          rightTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
          topTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
          bottomTitles: AxisTitles(
            sideTitles: SideTitles(
              showTitles: true,
              reservedSize: 30,
              interval: 1,
              getTitlesWidget: (value, meta) {
                if (value.toInt() >= 0 && value.toInt() < data.length) {
                  // Show e.g. "2023-10" -> "10" (Month)
                  final date = data[value.toInt()]['mois'].toString();
                  return Padding(
                    padding: const EdgeInsets.only(top: 8.0),
                    child: Text(
                      date.split('-').last,
                      style: TextStyle(color: Colors.grey[500], fontSize: 10),
                    ),
                  );
                }
                return const Text('');
              },
            ),
          ),
          leftTitles: AxisTitles(
            sideTitles: SideTitles(
              showTitles: true,
              interval: 20,
              getTitlesWidget: (value, meta) {
                return Text(
                  value.toInt().toString(),
                  style: TextStyle(color: Colors.grey[500], fontSize: 10),
                );
              },
              reservedSize: 30,
            ),
          ),
        ),
        borderData: FlBorderData(show: false),
        minX: 0,
        maxX: (data.length - 1).toDouble(),
        minY: 0,
        lineBarsData: [
          LineChartBarData(
            spots: spots,
            isCurved: true,
            color: const Color(0xFF3B82F6),
            barWidth: 3,
            isStrokeCapRound: true,
            dotData: const FlDotData(show: false),
            belowBarData: BarAreaData(
              show: true,
              color: const Color(0xFF3B82F6).withOpacity(0.1),
            ),
          ),
        ],
      ),
    );
  }
}

// --- KPI Doughnut Chart (Réparations) ---
class KpiDoughnutChart extends StatelessWidget {
  final Map<String, dynamic> stats;

  const KpiDoughnutChart({super.key, required this.stats});

  @override
  Widget build(BuildContext context) {
    final nouvelle = double.tryParse(stats['nb_nouvelles'].toString()) ?? 0;
    final enCours = double.tryParse(stats['nb_en_cours'].toString()) ?? 0;
    final effectuee = double.tryParse(stats['nb_effectuees'].toString()) ?? 0;
    final restituee = double.tryParse(stats['nb_restituees'].toString()) ?? 0;

    final total = nouvelle + enCours + effectuee + restituee;
    if (total == 0) return const Center(child: Text("Pas de données", style: TextStyle(color: Colors.grey)));

    return PieChart(
      PieChartData(
        sectionsSpace: 2,
        centerSpaceRadius: 40,
        sections: [
          PieChartSectionData(color: const Color(0xFFfbbf24), value: nouvelle, title: '', radius: 25), // Warning
          PieChartSectionData(color: const Color(0xFF06b6d4), value: enCours, title: '', radius: 25), // Info
          PieChartSectionData(color: const Color(0xFF22c55e), value: effectuee, title: '', radius: 25), // Success
          PieChartSectionData(color: const Color(0xFF3b82f6), value: restituee, title: '', radius: 25), // Primary
        ],
      ),
    );
  }
}

class KpiEmployeeTable extends StatelessWidget {
  final List<dynamic> employees;

  const KpiEmployeeTable({super.key, required this.employees});

  @override
  Widget build(BuildContext context) {
    if (employees.isEmpty) return const Center(child: Text("Aucun employé", style: TextStyle(color: Colors.grey)));

    return Container(
      decoration: BoxDecoration(
        color: const Color(0xFF1E293B),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.white.withOpacity(0.05)),
      ),
      child: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: const [
                Expanded(flex: 3, child: Text("Employé", style: TextStyle(color: Colors.grey, fontSize: 12))),
                Expanded(flex: 2, child: Text("Réparations", style: TextStyle(color: Colors.grey, fontSize: 12))),
                Expanded(flex: 2, child: Text("CA Encaissé", style: TextStyle(color: Colors.grey, fontSize: 12))),
                Expanded(flex: 2, child: Text("Panier Moyen", style: TextStyle(color: Colors.grey, fontSize: 12))),
              ],
            ),
          ),
          const Divider(height: 1, color: Colors.white10),
          ...employees.map((emp) => Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: const BoxDecoration(
              border: Border(bottom: BorderSide(color: Colors.white10)),
            ),
            child: Row(
              children: [
                Expanded(
                  flex: 3,
                  child: Row(
                    children: [
                      CircleAvatar(
                        radius: 12,
                        backgroundColor: Colors.grey[800],
                        backgroundImage: emp['employe_photo'] != null ? NetworkImage(emp['employe_photo']) : null,
                        child: emp['employe_photo'] == null 
                            ? Text((emp['employe_nom'] ?? 'U')[0], style: const TextStyle(fontSize: 10, color: Colors.white)) 
                            : null,
                      ),
                      const SizedBox(width: 8),
                      Expanded(child: Text(emp['employe_nom'] ?? 'Inconnu', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w500))),
                    ],
                  ),
                ),
                Expanded(flex: 2, child: Text("${emp['nb_total']}", style: const TextStyle(color: Colors.grey))),
                Expanded(flex: 2, child: Text("${emp['ca_encaisse']} €", style: const TextStyle(color: Color(0xFF22c55e), fontWeight: FontWeight.bold))),
                Expanded(flex: 2, child: Text("${emp['panier_moyen_total']} €", style: const TextStyle(color: Colors.grey))),
              ],
            ),
          )),
        ],
      ),
    );
  }
}
