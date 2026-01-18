import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:geekboard_desktop/config/api_config.dart';
import 'package:geekboard_desktop/widgets/sidebar.dart';
import 'package:geekboard_desktop/screens/formations/admin/dialogs/assign_formation_dialog.dart';
import 'package:geekboard_desktop/services/auth_service.dart';
import 'package:intl/intl.dart';

class AdminFormationScreen extends StatefulWidget {
  const AdminFormationScreen({super.key});

  @override
  State<AdminFormationScreen> createState() => _AdminFormationScreenState();
}

class _AdminFormationScreenState extends State<AdminFormationScreen> {
  bool _isLoading = true;
  Map<String, dynamic> _stats = {};
  List<dynamic> _usersProgress = [];
  List<dynamic> _recentActivity = [];
  Map<String, dynamic> _formationsConfig = {};
  
  // Filter state
  String _filter = 'all'; // all, in-progress, completed

  @override
  void initState() {
    super.initState();
    _fetchData();
  }

  Future<void> _fetchData() async {
    setState(() => _isLoading = true);
    try {
      final apiService = context.read<AuthService>().getApiService();
      final data = await apiService.get(ApiConfig.formationAdminDashboardEndpoint);
      if (data['success'] == true) {
        setState(() {
          _stats = data['data']['stats'] ?? {};
          _usersProgress = data['data']['users_progress'] ?? [];
          _recentActivity = data['data']['recent_activity'] ?? [];
          _formationsConfig = data['data']['formations_config'] ?? {};
          _isLoading = false;
        });
      } else {
        if (mounted) setState(() => _isLoading = false);
      }
    } catch (e) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      // backgroundColor: const Color(0xFF0F172A), // Removed to use Theme
      body: Row(
        children: [
          const Sidebar(currentRoute: '/admin_formation'),
          Expanded(
            child: _isLoading 
                ? const Center(child: CircularProgressIndicator())
                : SingleChildScrollView(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Header
                        _buildHeader(),
                        const SizedBox(height: 24),
                        
                        // Stats Grid
                        _buildStatsGrid(),
                        const SizedBox(height: 24),
                        
                        // Main Content
                        Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            // Progress Table
                            Expanded(child: _buildProgressTable()),
                            const SizedBox(width: 24),
                            // Recent Activity
                            if (MediaQuery.of(context).size.width > 1200)
                              SizedBox(width: 350, child: _buildRecentActivity()),
                          ],
                        ),
                        
                        // Activity feed for smaller screens (below table)
                        if (MediaQuery.of(context).size.width <= 1200) ...[
                          const SizedBox(height: 24),
                          _buildRecentActivity(),
                        ],
                      ],
                    ),
                  ),
          ),
        ],
      ),
    );
  }

  Widget _buildHeader() {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Row(
              children: [
                Icon(Icons.school, color: Colors.blue, size: 28),
                SizedBox(width: 12),
                Text('Suivi des Formations', style: TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.bold)),
              ],
            ),
            const SizedBox(height: 4),
            Text('Tableau de bord administrateur', style: TextStyle(color: Colors.white.withOpacity(0.7))),
          ],
        ),
        Row(
          children: [
            // Filter buttons
            Container(
              decoration: BoxDecoration(
                color: const Color(0xFF1E293B),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: const Color(0xFF334155)),
              ),
              child: Row(
                children: [
                  _buildFilterBtn('Tous', 'all'),
                  _buildFilterBtn('En cours', 'in_progress'), // Note: API filters usually done frontend here for simple lists
                  _buildFilterBtn('Terminées', 'completed'),
                ],
              ),
            ),
            const SizedBox(width: 16),
            ElevatedButton.icon(
              onPressed: () => showDialog(
                context: context, 
                builder: (_) => AssignFormationDialog(
                  users: _usersProgress.map((u) => u['user']).toList(),
                  formationsConfig: _formationsConfig,
                  onSuccess: _fetchData
                )
              ),
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF10B981),
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
              icon: const Icon(Icons.person_add, color: Colors.white),
              label: const Text('Assigner', style: TextStyle(color: Colors.white)),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildFilterBtn(String label, String value) {
    final isActive = _filter == value;
    return InkWell(
      onTap: () => setState(() => _filter = value),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        decoration: BoxDecoration(
          color: isActive ? Colors.blue : null,
          borderRadius: BorderRadius.circular(7),
        ),
        child: Text(
          label, 
          style: TextStyle(
            color: isActive ? Colors.white : Colors.grey, 
            fontWeight: FontWeight.w500
          )
        ),
      ),
    );
  }

  Widget _buildStatsGrid() {
    return GridView.count(
      crossAxisCount: 4,
      crossAxisSpacing: 16,
      mainAxisSpacing: 16,
      childAspectRatio: 2.5,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      children: [
        _buildStatCard(
          'Employés actifs', 
          '${_stats['total_users']}', 
          Icons.people, 
          Colors.blue
        ),
        _buildStatCard(
          'Formations démarrées', 
          '${_stats['formations_started']}', 
          Icons.play_circle_fill, 
          Colors.purple
        ),
        _buildStatCard(
          'Formations terminées', 
          '${_stats['formations_completed']}', 
          Icons.check_circle, 
          Colors.green
        ),
        _buildStatCard(
          'Progression moyenne', 
          '${_stats['avg_completion']}%', 
          Icons.show_chart, 
          Colors.orange
        ),
      ],
    );
  }

  Widget _buildStatCard(String label, String value, IconData icon, Color color) {
    return Card(
      elevation: 0,
      margin: EdgeInsets.zero,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(color: Theme.of(context).dividerColor),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Container(
              width: 48,
              height: 48,
              decoration: BoxDecoration(
                color: color.withOpacity(0.2),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(icon, color: color, size: 24),
            ),
            const SizedBox(width: 16),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(value, style: TextStyle(color: Theme.of(context).textTheme.bodyLarge?.color, fontSize: 24, fontWeight: FontWeight.bold)),
                Text(label, style: TextStyle(color: Theme.of(context).textTheme.bodySmall?.color, fontSize: 13)),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildProgressTable() {
    var filteredUsers = _usersProgress;
    // Basic frontend filtering logic if needed (Assuming API returns all)
    // Here we might filter users based on whether they have ANY formation in the specific state
    // But typically user list is static. The filter button might filter users who have at least one formation "in progress".
    // For simplicity, I'll keep full list or implement basic filter.
    if (_filter == 'in_progress') {
       filteredUsers = _usersProgress.where((u) => (u['avg_progress'] > 0 && u['avg_progress'] < 100)).toList();
    } else if (_filter == 'completed') {
       filteredUsers = _usersProgress.where((u) => u['avg_progress'] == 100).toList();
    }

    return Card(
      elevation: 0,
      margin: EdgeInsets.zero,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(color: Theme.of(context).dividerColor),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.all(20),
            child: Row(
              children: [
                const Icon(Icons.table_chart, color: Colors.blue, size: 20),
                const SizedBox(width: 12),
                Text('Progression des employés', style: TextStyle(color: Theme.of(context).textTheme.titleMedium?.color, fontSize: 16, fontWeight: FontWeight.bold)),
              ],
            ),
          ),
          Divider(height: 1, color: Theme.of(context).dividerColor),
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: DataTable(
              headingRowColor: MaterialStateProperty.all(Colors.transparent),
              dataRowColor: MaterialStateProperty.all(Colors.transparent),
              columnSpacing: 24,
              columns: const [
                DataColumn(label: Text('EMPLOYÉ', style: TextStyle(color: Colors.grey, fontSize: 12))),
                DataColumn(label: Text('FORMATIONS', style: TextStyle(color: Colors.grey, fontSize: 12))),
                DataColumn(label: Text('PROGRESSION', style: TextStyle(color: Colors.grey, fontSize: 12))),
                DataColumn(label: Text('ACTIVITÉ', style: TextStyle(color: Colors.grey, fontSize: 12))),
              ],
              rows: filteredUsers.map<DataRow>((userItem) {
                final user = userItem['user'];
                final progress = userItem['avg_progress'];
                final lastActivity = userItem['last_activity'];
                final formations = userItem['formations_status'] as List<dynamic>;

                return DataRow(
                  cells: [
                    // User
                    DataCell(Row(
                      children: [
                        CircleAvatar(
                          backgroundColor: Colors.blue.shade900,
                          radius: 16,
                          child: Text(
                            (user['full_name'] as String).substring(0, 2).toUpperCase(),
                            style: const TextStyle(fontSize: 12, color: Colors.white),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Text(user['full_name'], style: TextStyle(color: Theme.of(context).textTheme.bodyMedium?.color, fontWeight: FontWeight.bold)),
                            Text(user['role'], style: TextStyle(color: Theme.of(context).textTheme.bodySmall?.color, fontSize: 11)),
                          ],
                        ),
                      ],
                    )),
                    // Formations Pills
                    DataCell(Row(
                      children: formations.map<Widget>((f) {
                        Color color = const Color(0xFF334155); // Default
                        IconData icon = Icons.circle;
                        if (f['status'] == 'completed') {
                          color = const Color(0xFF10B981);
                          icon = Icons.check;
                        } else if (f['status'] == 'in_progress') {
                          color = const Color(0xFF3B82F6);
                          icon = Icons.donut_large;
                        }
                        
                        return Padding(
                          padding: const EdgeInsets.only(right: 4),
                          child: Tooltip(
                            message: f['titre'],
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                              decoration: BoxDecoration(
                                color: color.withOpacity(0.2),
                                borderRadius: BorderRadius.circular(4),
                              ),
                              child: Row(
                                children: [
                                  Icon(icon, size: 10, color: color),
                                  const SizedBox(width: 4),
                                  Text('${f['id']}', style: TextStyle(color: color, fontSize: 10, fontWeight: FontWeight.bold)),
                                ],
                              ),
                            ),
                          ),
                        );
                      }).toList(),
                    )),
                    // Progress Bar
                    DataCell(SizedBox(
                      width: 100,
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          LinearProgressIndicator(
                            value: (progress as num).toDouble() / 100,
                            backgroundColor: const Color(0xFF334155),
                            valueColor: AlwaysStoppedAnimation<Color>(
                              progress < 30 ? Colors.red : (progress < 70 ? Colors.orange : Colors.green)
                            ),
                            minHeight: 6,
                            borderRadius: BorderRadius.circular(3),
                          ),
                          const SizedBox(height: 4),
                          Text('$progress%', style: TextStyle(color: Theme.of(context).textTheme.bodySmall?.color, fontSize: 11)),
                        ],
                      ),
                    )),
                    // Last Activity
                    DataCell(Text(
                      lastActivity != null 
                        ? DateFormat('dd/MM HH:mm').format(DateTime.parse(lastActivity))
                        : 'Jamais',
                      style: TextStyle(color: Theme.of(context).textTheme.bodySmall?.color, fontSize: 12),
                    )),
                  ],
                );
              }).toList(),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildRecentActivity() {
    return Card(
      elevation: 0,
      margin: EdgeInsets.zero,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(color: Theme.of(context).dividerColor),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.all(20),
            child: Row(
              children: [
                const Icon(Icons.history, color: Colors.blue, size: 20),
                const SizedBox(width: 12),
                Text('Activité récente', style: TextStyle(color: Theme.of(context).textTheme.titleMedium?.color, fontSize: 16, fontWeight: FontWeight.bold)),
              ],
            ),
          ),
          Divider(height: 1, color: Theme.of(context).dividerColor),
          if (_recentActivity.isEmpty)
            Padding(
              padding: const EdgeInsets.all(24),
              child: Center(
                child: Text('Aucune activité récente', style: TextStyle(color: Theme.of(context).textTheme.bodySmall?.color)),
              ),
            )
          else
            ListView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: _recentActivity.length,
              itemBuilder: (context, index) {
                final activity = _recentActivity[index];
                
                IconData icon;
                Color color;
                String text;

                switch (activity['action_type']) {
                  case 'start':
                    icon = Icons.play_arrow;
                    color = Colors.blue;
                    text = 'a démarré';
                    break;
                  case 'complete':
                    icon = Icons.check;
                    color = Colors.green;
                    text = 'a terminé';
                    break;
                  case 'step_complete':
                    icon = Icons.arrow_forward;
                    color = Colors.purple;
                    text = "étape ${activity['step_number']}";
                    break;
                  default:
                    icon = Icons.circle;
                    color = Colors.grey;
                    text = 'activité';
                }

                return ListTile(
                  leading: Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: color.withOpacity(0.2),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Icon(icon, color: color, size: 16),
                  ),
                  title: RichText(
                    text: TextSpan(
                      style: TextStyle(fontSize: 13, color: Theme.of(context).textTheme.bodyMedium?.color),
                      children: [
                        TextSpan(text: activity['full_name'], style: const TextStyle(fontWeight: FontWeight.bold)),
                        TextSpan(text: ' $text '),
                        TextSpan(text: activity['formation_titre'], style: TextStyle(fontStyle: FontStyle.italic, color: Theme.of(context).textTheme.bodySmall?.color)),
                      ],
                    ),
                  ),
                  subtitle: Text(
                    DateFormat('dd/MM HH:mm').format(DateTime.parse(activity['created_at'])),
                    style: TextStyle(color: Theme.of(context).textTheme.bodySmall?.color, fontSize: 11),
                  ),
                );
              },
            ),
        ],
      ),
    );
  }
}
