import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../../services/auth_service.dart';
import '../../../../config/api_config.dart';
import '../../../../services/api_service.dart';

class NotificationsTab extends StatefulWidget {
  final bool isAdmin;
  const NotificationsTab({super.key, required this.isAdmin});

  @override
  State<NotificationsTab> createState() => _NotificationsTabState();
}

class _NotificationsTabState extends State<NotificationsTab> with SingleTickerProviderStateMixin {
  late final ApiService _apiService;
  bool _isLoading = true;
  late TabController _tabController;
  
  // Data for each tab
  // Modes: 'personal', 'admin', 'technicien'
  Map<String, List<Map<String, dynamic>>> _preferences = {
    'personal': [],
    'admin': [],
    'technicien': [],
  };

  @override
  void initState() {
    super.initState();
    _apiService = context.read<AuthService>().getApiService();
    
    // Check admin from AuthService if available, or widget prop
    final authService = context.read<AuthService>();
    final isAdmin = widget.isAdmin || (authService.currentUser?.role == 'admin' || authService.currentUser?.role == 'superadmin');

    // 3 tabs if admin, else 1
    _tabController = TabController(length: isAdmin ? 3 : 1, vsync: this);
    _loadAllPreferences();
  }
  
  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _loadAllPreferences() async {
    setState(() => _isLoading = true);
    await _loadPreferences('personal');
    // Check if we initialized with admin tabs
    if (_tabController.length == 3) {
      await _loadPreferences('admin');
      await _loadPreferences('technicien');
    }
    if (mounted) setState(() => _isLoading = false);
  }

  Future<void> _loadPreferences(String mode) async {
    try {
      final response = await _apiService.get(
        ApiConfig.settingsNotificationsEndpoint,
        {'mode': mode}
      );
      
      if (response != null && response['success'] == true && response['data'] != null) {
        if (mounted) {
           setState(() {
             _preferences[mode] = List<Map<String, dynamic>>.from(response['data']);
           });
        }
      }
    } catch (e) {
      print('Error loading preferences for $mode: $e');
    }
  }

  Future<void> _updatePreference(String mode, String typeCode, String key, bool value) async {
    // Optimistic update
    final index = _preferences[mode]!.indexWhere((p) => p['type_code'] == typeCode);
    if (index == -1) return;
    
    final oldState = Map<String, dynamic>.from(_preferences[mode]![index]);
    
    setState(() {
      _preferences[mode]![index][key] = value;
      
      // Logic: if active is false, email/push disabled visually (handled in build), 
      // but if active becomes true, we might want defaults? 
      // The web logic: if email/push unchecked, they stay unchecked.
      
      // Logic from web: ensure at least one method is active if notification is active
      if (key == 'active' && value == true) {
         // check if both email and push are false, if so enable push by default?
         // Web logic: "handleToggle(true)" just enables the switches.
         // Web "methodToggles": "S'assurer qu'au moins une méthode est active si la notification est activée"
         // If user unchecks the last one, it rechecks it.
      }
      
      if (key == 'email' || key == 'push') {
        final isActive = _preferences[mode]![index]['active'];
        if (isActive) {
           final isEmail = _preferences[mode]![index]['email'];
           final isPush = _preferences[mode]![index]['push'];
           if (!isEmail && !isPush) {
             // Revert the change because one must be active
             _preferences[mode]![index][key] = !value;
             ScaffoldMessenger.of(context).showSnackBar(
               const SnackBar(content: Text("Au moins un canal de notification doit être activé.")),
             );
             return; // Don't call API
           }
        }
      }
    });

    try {
      final payload = {
        'mode': mode,
        'type_code': typeCode,
        'active': _preferences[mode]![index]['active'] ? 1 : 0,
        'email': _preferences[mode]![index]['email'] ? 1 : 0,
        'push': _preferences[mode]![index]['push'] ? 1 : 0,
      };
      
      final response = await _apiService.post(
        ApiConfig.settingsNotificationsEndpoint,
        payload
      );
      
      if (response == null || (response['success'] != true && response['message'] != 'Préférence mise à jour')) {
        throw Exception(response?['message'] ?? 'Erreur inconnue');
      }
    } catch (e) {
      if (mounted) {
        setState(() {
           _preferences[mode]![index] = oldState; // Revert
        });
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erreur lors de la mise à jour: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }
    
    final isAdmin = _tabController.length == 3;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (isAdmin)
          Container(
            margin: const EdgeInsets.only(bottom: 24),
            decoration: BoxDecoration(
              color: Theme.of(context).cardColor,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: Theme.of(context).dividerColor),
            ),
            child: TabBar(
              controller: _tabController,
              labelColor: Theme.of(context).primaryColor,
              unselectedLabelColor: Colors.grey,
              indicatorColor: Theme.of(context).primaryColor,
              indicatorSize: TabBarIndicatorSize.label,
              padding: const EdgeInsets.symmetric(vertical: 6),
              tabs: const [
                Tab(text: "Mes préférences", icon: Icon(Icons.person, size: 20)),
                Tab(text: "Admins", icon: Icon(Icons.admin_panel_settings, size: 20)),
                Tab(text: "Employés", icon: Icon(Icons.engineering, size: 20)),
              ],
            ),
          ),
          
        if (!isAdmin)
           const Padding(
             padding: EdgeInsets.only(bottom: 16),
             child: Text(
              "Gérez vos préférences de notification personnelles.",
              style: TextStyle(fontSize: 14, color: Colors.grey),
             ),
           ),

        Expanded(
          child: isAdmin 
            ? TabBarView(
                controller: _tabController,
                children: [
                  _buildNotificationList('personal'),
                  _buildNotificationList('admin'),
                  _buildNotificationList('technicien'),
                ],
              )
            : _buildNotificationList('personal'),
        ),
      ],
    );
  }

  Widget _buildNotificationList(String mode) {
    final prefs = _preferences[mode] ?? [];
    
    if (prefs.isEmpty) {
      return const Center(child: Text("Aucune notification disponible"));
    }

    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: BorderSide(color: Theme.of(context).dividerColor),
      ),
      child: Column(
        children: [
          _buildHeaderRow(),
          const Divider(height: 1),
          Expanded(
            child: ListView.separated(
              itemCount: prefs.length,
              separatorBuilder: (ctx, i) => const Divider(height: 1),
              itemBuilder: (context, index) {
                return _buildNotificationRow(mode, prefs[index]);
              },
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildHeaderRow() {
    return Padding(
      padding: const EdgeInsets.all(16.0),
      child: Row(
        children: [
          Expanded(
            child: Text(
              "Type de notification", 
              style: TextStyle(
                color: Theme.of(context).textTheme.bodyLarge?.color, 
                fontWeight: FontWeight.bold,
                fontSize: 13
              )
            )
          ),
          SizedBox(width: 80, child: Center(child: Text("Activer", style: TextStyle( fontSize: 13, color: Theme.of(context).textTheme.bodyLarge?.color, fontWeight: FontWeight.bold)))),
          SizedBox(width: 80, child: Center(child: Text("Email", style: TextStyle(fontSize: 13, color: Theme.of(context).textTheme.bodyLarge?.color, fontWeight: FontWeight.bold)))),
          SizedBox(width: 80, child: Center(child: Text("Push", style: TextStyle(fontSize: 13, color: Theme.of(context).textTheme.bodyLarge?.color, fontWeight: FontWeight.bold)))),
        ],
      ),
    );
  }

  Widget _buildNotificationRow(String mode, Map<String, dynamic> item) {
    final isActive = item['active'] == true;
    final importance = item['importance'] ?? 'normal'; // 'critique', 'haute', 'normal'
    
    Color iconColor;
    if (importance == 'critique') iconColor = Colors.red;
    else if (importance == 'haute') iconColor = Colors.orange;
    else iconColor = Colors.blue;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 12.0),
      color: !isActive ? Theme.of(context).disabledColor.withOpacity(0.05) : null,
      child: Row(
        children: [
          // Icon & Label
          Expanded(
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: iconColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Icon(
                    _getIconData(item['icon']),
                    color: iconColor,
                    size: 18,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        item['description'] ?? 'Notification',
                        style: TextStyle(
                          color: isActive 
                            ? Theme.of(context).textTheme.bodyLarge?.color 
                            : Theme.of(context).textTheme.bodyMedium?.color?.withOpacity(0.6),
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                      if (importance != 'normal')
                        Padding(
                          padding: const EdgeInsets.only(top: 4),
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                            decoration: BoxDecoration(
                              color: iconColor.withOpacity(0.1),
                              borderRadius: BorderRadius.circular(4),
                            ),
                            child: Text(
                              importance.toUpperCase(),
                              style: TextStyle(color: iconColor, fontSize: 8, fontWeight: FontWeight.bold),
                            ),
                          ),
                        )
                    ],
                  ),
                ),
              ],
            ),
          ),
          
          // Active Switch
          SizedBox(
            width: 80,
            child: Center(
              child: Switch(
                value: isActive,
                onChanged: (val) => _updatePreference(mode, item['type_code'], 'active', val),
                activeColor: Colors.green,
              ),
            ),
          ),
          
          // Email Switch
          SizedBox(
            width: 80,
            child: Center(
              child: Switch(
                value: item['email'] == true,
                onChanged: isActive ? (val) => _updatePreference(mode, item['type_code'], 'email', val) : null,
                activeColor: Colors.blue,
              ),
            ),
          ),
          
          // Push Switch
          SizedBox(
            width: 80,
            child: Center(
              child: Switch(
                value: item['push'] == true,
                onChanged: isActive ? (val) => _updatePreference(mode, item['type_code'], 'push', val) : null,
                activeColor: Colors.purple,
              ),
            ),
          ),
        ],
      ),
    );
  }

  IconData _getIconData(String? iconClass) {
    // Map FontAwesome classes to Material Icons approx
    if (iconClass == null) return Icons.notifications;
    if (iconClass.contains('bell')) return Icons.notifications;
    if (iconClass.contains('envelope')) return Icons.email;
    if (iconClass.contains('box')) return Icons.inventory_2;
    if (iconClass.contains('euro')) return Icons.euro;
    if (iconClass.contains('file')) return Icons.description;
    if (iconClass.contains('check')) return Icons.check_circle;
    if (iconClass.contains('exclamation')) return Icons.warning;
    if (iconClass.contains('tools')) return Icons.build;
    if (iconClass.contains('user')) return Icons.person;
    
    return Icons.notifications_active;
  }
}
