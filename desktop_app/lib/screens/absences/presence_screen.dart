import 'package:flutter/material.dart';
import '../../widgets/app_shell.dart';
import '../../services/api_service.dart';
import '../../services/auth_service.dart';
import '../../config/api_config.dart';
import 'package:provider/provider.dart';
import '../../widgets/presence_cards.dart';
import 'new_presence_dialog.dart';
import 'package:url_launcher/url_launcher.dart';

class PresenceScreen extends StatefulWidget {
  const PresenceScreen({super.key});

  @override
  State<PresenceScreen> createState() => _PresenceScreenState();
}

class _PresenceScreenState extends State<PresenceScreen> {
  ApiService get _apiService => context.read<AuthService>().getApiService();
  
  // Data
  List<dynamic> _events = [];
  Map<String, dynamic> _stats = {};
  Map<String, dynamic> _filters = {};
  bool _isLoading = true;
  bool _isAdmin = false;

  // Filter State
  String? _selectedTypeId;
  String? _selectedStatus;
  String? _selectedUserId;
  int? _selectedMonth;
  int? _selectedYear;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    try {
      String url = '${ApiConfig.presenceListEndpoint}?';
      if (_selectedTypeId != null) url += 'type=$_selectedTypeId&';
      if (_selectedStatus != null) url += 'status=$_selectedStatus&';
      if (_selectedUserId != null) url += 'user=$_selectedUserId&';
      if (_selectedMonth != null) url += 'month=$_selectedMonth&';
      if (_selectedYear != null) url += 'year=$_selectedYear&';

      final response = await _apiService.get(url);
      
      if (mounted) {
        setState(() {
          _events = response['events'] ?? [];
          _stats = response['stats'] ?? {};
          _filters = response['filters'] ?? {};
          _isAdmin = response['is_admin'] ?? false;
          _isLoading = false;
        });
      }
    } catch (e) {
      print('Error loading presence: $e');
      if (mounted) {
        setState(() => _isLoading = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }

  Future<void> _exportData() async {
    try {
      final subdomain = context.read<AuthService>().getSubdomain();
      // Temporary fallback to web interface for export
      final url = Uri.parse('https://$subdomain.servo.tools/index.php?page=conges');
      if (await canLaunchUrl(url)) {
        await launchUrl(url);
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Impossible d\'ouvrir le lien d\'export')),
        );
      }
    } catch (e) {
      print('Export error: $e');
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final backgroundColor = Theme.of(context).scaffoldBackgroundColor;
    final cardColor = Theme.of(context).cardColor;
    final textColor = Theme.of(context).textTheme.bodyLarge?.color ?? Colors.black;
    final borderColor = isDark ? Colors.white.withOpacity(0.05) : const Color(0xFFE5E5E7);

    return AppShell(
      currentRoute: '/absences',
      content: Scaffold(
        backgroundColor: backgroundColor,
        appBar: AppBar(
          backgroundColor: cardColor,
          elevation: 0,
          // No leading back button needed with AppShell
          title: Text('Gestion des Absences', style: TextStyle(color: textColor, fontWeight: FontWeight.bold)),
          actions: [
            Padding(
              padding: const EdgeInsets.only(right: 16.0),
              child: ElevatedButton.icon(
                onPressed: _exportData,
                icon: const Icon(Icons.download, size: 18),
                label: const Text('Access Web / Export'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: isDark ? Colors.white.withOpacity(0.1) : Colors.grey[200],
                  foregroundColor: textColor,
                  elevation: 0,
                ),
              ),
            ),
          ],
          bottom: PreferredSize(
            preferredSize: const Size.fromHeight(1),
            child: Container(color: borderColor, height: 1),
          ),
        ),
        body: Column(
          children: [
            // Header Stats
            _buildStatsHeader(isDark, cardColor, borderColor, textColor),
            
            // Filter Bar
            _buildFilterBar(isDark, cardColor, borderColor, textColor),
            
            // List
            Expanded(
              child: _isLoading 
                ? const Center(child: CircularProgressIndicator()) 
                : _events.isEmpty
                  ? _buildEmptyState(textColor)
                  : ListView.builder(
                      padding: const EdgeInsets.all(24),
                      itemCount: _events.length,
                      itemBuilder: (context, index) {
                        return PresenceEventCard(
                          event: _events[index] as Map<String, dynamic>,
                        );
                      },
                    ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatsHeader(bool isDark, Color cardColor, Color borderColor, Color textColor) {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1E293B) : Colors.white,
        border: Border(bottom: BorderSide(color: borderColor)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xFF06b6d4), Color(0xFF3b82f6)],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(Icons.access_time_filled, color: Colors.white, size: 24),
              ),
              const SizedBox(width: 16),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Présence & Absences',
                    style: TextStyle(
                      fontSize: 24,
                      fontWeight: FontWeight.w800,
                      color: textColor,
                      letterSpacing: -0.5,
                    ),
                  ),
                  Text(
                    'Gérez vos retards, absences et congés',
                    style: TextStyle(color: isDark ? Colors.grey : const Color(0xFF86868B), fontSize: 13),
                  ),
                ],
              ),
            ],
          ),
          
          const SizedBox(height: 24),
          
          Row(
            children: [
              Expanded(child: _buildClickableStat(
                label: 'En attente', 
                count: _stats['pending']?.toString() ?? '0', 
                color: const Color(0xFFF59E0B), 
                icon: Icons.hourglass_top,
                statusValue: 'pending',
              )),
              const SizedBox(width: 16),
              Expanded(child: _buildClickableStat(
                label: 'Approuvé', 
                count: _stats['approved']?.toString() ?? '0', 
                color: const Color(0xFF10B981), 
                icon: Icons.check_circle_outline,
                statusValue: 'approved',
              )),
              const SizedBox(width: 16),
              Expanded(child: _buildClickableStat(
                label: 'Rejeté', 
                count: _stats['rejected']?.toString() ?? '0', 
                color: const Color(0xFFEF4444), 
                icon: Icons.cancel_outlined,
                statusValue: 'rejected',
              )),
              const SizedBox(width: 16),
              Expanded(child: _buildClickableStat(
                label: 'Total', 
                count: _stats['total']?.toString() ?? '0', 
                color: const Color(0xFF3B82F6), 
                icon: Icons.list_alt,
                statusValue: null, // null means "all"
              )),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildFilterBar(bool isDark, Color cardColor, Color borderColor, Color textColor) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1E293B).withOpacity(0.5) : Colors.white.withOpacity(0.5),
        border: Border(bottom: BorderSide(color: borderColor)),
      ),
      child: Row(
        children: [
          // Filter by Type
          _buildDropdown(
            isDark: isDark,
            value: _selectedTypeId,
            items: [
              const DropdownMenuItem(value: null, child: Text("Tous les types")),
              ...(_filters['types'] as List<dynamic>? ?? []).map((t) => 
                DropdownMenuItem(value: t['id'].toString(), child: Text(t['name']))
              ),
            ],
            onChanged: (v) {
              setState(() => _selectedTypeId = v as String?);
              _loadData();
            },
            textColor: textColor,
          ),
          
          const SizedBox(width: 16),
          
          // Filter by Status
          _buildDropdown(
            isDark: isDark,
            value: _selectedStatus,
            items: const [
               DropdownMenuItem(value: null, child: Text("Tous les statuts")),
               DropdownMenuItem(value: 'pending', child: Text("En attente")),
               DropdownMenuItem(value: 'approved', child: Text("Approuvé")),
               DropdownMenuItem(value: 'rejected', child: Text("Rejeté")),
            ],
            onChanged: (v) {
               setState(() => _selectedStatus = v as String?);
               _loadData();
            },
            textColor: textColor,
          ),

          // Filter by Employee (Admin only)
          if (_isAdmin && (_filters['team'] as List?)?.isNotEmpty == true) ...[
            const SizedBox(width: 16),
            _buildDropdown(
              isDark: isDark,
              value: _selectedUserId,
              items: [
                const DropdownMenuItem(value: null, child: Text("Tous les employés")),
                ...(_filters['team'] as List<dynamic>).map((e) => 
                  DropdownMenuItem(value: e['id'].toString(), child: Text(e['full_name'] ?? 'N/A'))
                ),
              ],
              onChanged: (v) {
                setState(() => _selectedUserId = v as String?);
                _loadData();
              },
              textColor: textColor,
            ),
          ],

          const SizedBox(width: 16),

          // Filter by Month
          _buildDropdown(
            isDark: isDark,
            value: _selectedMonth,
            items: [
              const DropdownMenuItem<int>(value: null, child: Text("Tous les mois")),
              const DropdownMenuItem<int>(value: 1, child: Text("Janvier")),
              const DropdownMenuItem<int>(value: 2, child: Text("Février")),
              const DropdownMenuItem<int>(value: 3, child: Text("Mars")),
              const DropdownMenuItem<int>(value: 4, child: Text("Avril")),
              const DropdownMenuItem<int>(value: 5, child: Text("Mai")),
              const DropdownMenuItem<int>(value: 6, child: Text("Juin")),
              const DropdownMenuItem<int>(value: 7, child: Text("Juillet")),
              const DropdownMenuItem<int>(value: 8, child: Text("Août")),
              const DropdownMenuItem<int>(value: 9, child: Text("Septembre")),
              const DropdownMenuItem<int>(value: 10, child: Text("Octobre")),
              const DropdownMenuItem<int>(value: 11, child: Text("Novembre")),
              const DropdownMenuItem<int>(value: 12, child: Text("Décembre")),
            ],
            onChanged: (v) {
              setState(() => _selectedMonth = v as int?);
              _loadData();
            },
            textColor: textColor,
          ),

          const SizedBox(width: 16),

          // Filter by Year
          _buildDropdown(
            isDark: isDark,
            value: _selectedYear,
            items: [
              const DropdownMenuItem<int>(value: null, child: Text("Toutes les années")),
              ...List.generate(5, (i) => DateTime.now().year - i).map((y) =>
                DropdownMenuItem<int>(value: y, child: Text(y.toString()))
              ),
            ],
            onChanged: (v) {
              setState(() => _selectedYear = v as int?);
              _loadData();
            },
            textColor: textColor,
          ),

          const Spacer(),
          
          ElevatedButton.icon(
            onPressed: () {
               showDialog(
                 context: context,
                 builder: (context) => NewPresenceDialog(
                   types: _filters['types'] ?? [],
                   onSuccess: _loadData,
                 ),
               );
            }, 
            icon: const Icon(Icons.add),
            label: const Text("Nouvelle Demande"),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF3b82f6),
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
          )
        ],
      ),
    );
  }

  Widget _buildDropdown({
    required bool isDark,
    required dynamic value,
    required List<DropdownMenuItem<dynamic>> items,
    required Function(dynamic) onChanged,
    required Color textColor,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF0F172A) : Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: isDark ? Colors.white.withOpacity(0.1) : const Color(0xFFD1D1D6)),
      ),
      child: DropdownButtonHideUnderline(
        child: DropdownButton(
          value: value,
          items: items,
          onChanged: onChanged,
          dropdownColor: isDark ? const Color(0xFF1E293B) : Colors.white,
          style: TextStyle(color: textColor),
          icon: Icon(Icons.arrow_drop_down, color: isDark ? Colors.grey : const Color(0xFF86868B)),
        ),
      ),
    );
  }

  Widget _buildClickableStat({
    required String label,
    required String count,
    required Color color,
    required IconData icon,
    required String? statusValue,
  }) {
    final bool isSelected = _selectedStatus == statusValue;
    return InkWell(
      onTap: () {
        setState(() => _selectedStatus = statusValue);
        _loadData();
      },
      borderRadius: BorderRadius.circular(12),
      child: Container(
        decoration: BoxDecoration(
          border: isSelected ? Border.all(color: color, width: 2) : null,
          borderRadius: BorderRadius.circular(12),
        ),
        child: PresenceStatCard(
          label: label,
          count: count,
          color: color,
          icon: icon,
        ),
      ),
    );
  }

  Widget _buildEmptyState(Color textColor) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.event_busy, size: 64, color: Colors.grey[700]),
          const SizedBox(height: 16),
          Text(
            'Aucun événement trouvé',
            style: TextStyle(color: textColor.withOpacity(0.6), fontSize: 16),
          ),
        ],
      ),
    );
  }
}
