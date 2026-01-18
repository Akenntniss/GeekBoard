import 'package:flutter/material.dart';
import '../../widgets/app_shell.dart';
import '../../services/api_service.dart';
import '../../services/auth_service.dart';
import '../../config/api_config.dart';
import 'package:provider/provider.dart';
import '../../widgets/presence_cards.dart';
import 'new_presence_dialog.dart';
import '../../widgets/time_tracking_widget.dart';

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
        body: Row(
          children: [
            // Left: Main Content
            Expanded(
              flex: 3,
              child: Column(
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
            
            // Right: Time Tracking Widget
            Container(
              width: 320,
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: isDark ? const Color(0xFF1E293B) : Colors.white,
                border: Border(left: BorderSide(color: borderColor)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Mon Pointage',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: textColor),
                  ),
                  const SizedBox(height: 16),
                  const TimeTrackingWidget(),
                ],
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
              PresenceStatCard(
                label: 'En attente', 
                count: _stats['pending']?.toString() ?? '0', 
                color: const Color(0xFFF59E0B), 
                icon: Icons.hourglass_top
              ),
              const SizedBox(width: 16),
              PresenceStatCard(
                label: 'Approuvé', 
                count: _stats['approved']?.toString() ?? '0', 
                color: const Color(0xFF10B981), 
                icon: Icons.check_circle_outline
              ),
              const SizedBox(width: 16),
              PresenceStatCard(
                label: 'Rejeté', 
                count: _stats['rejected']?.toString() ?? '0', 
                color: const Color(0xFFEF4444), 
                icon: Icons.cancel_outlined
              ),
              const SizedBox(width: 16),
               PresenceStatCard(
                label: 'Total', 
                count: _stats['total']?.toString() ?? '0', 
                color: const Color(0xFF3B82F6), 
                icon: Icons.list_alt
              ),
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
