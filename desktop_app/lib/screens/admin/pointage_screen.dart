/// Pointage Screen (Admin) - Full Version with Pending Tab
import 'package:flutter/material.dart';
import 'package:flutter/cupertino.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../../services/auth_service.dart';
import '../../theme/macos_theme.dart';
import '../../widgets/app_shell.dart';

class PointageScreen extends StatefulWidget {
  const PointageScreen({super.key});
  @override
  State<PointageScreen> createState() => _PointageScreenState();
}

class _PointageScreenState extends State<PointageScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  
  // Data
  List<Map<String, dynamic>> _activeUsers = [];
  List<Map<String, dynamic>> _dailyEntries = [];
  List<Map<String, dynamic>> _pendingEntries = [];
  bool _isLoading = true;
  DateTime _selectedDate = DateTime.now();

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 4, vsync: this);
    _loadData();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    try {
      final api = context.read<AuthService>().getApiService();
      final date = DateFormat('yyyy-MM-dd').format(_selectedDate);
      final response = await api.get('/admin/pointage.php?date=$date');
      
      if (mounted) {
        setState(() {
          _dailyEntries = List<Map<String, dynamic>>.from(response['pointages'] ?? []);
          _activeUsers = List<Map<String, dynamic>>.from(response['active_users'] ?? []);
          _pendingEntries = List<Map<String, dynamic>>.from(response['pending_entries'] ?? []);
          _isLoading = false;
        });
      }
    } catch (e) {
      debugPrint('Pointage Error: $e');
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final bgColor = isDark ? const Color(0xFF0F172A) : const Color(0xFFF8FAFC);
    final cardColor = isDark ? const Color(0xFF1E293B) : Colors.white;
    final textColor = isDark ? Colors.white : Colors.black87;

    return AppShell(
      currentRoute: '/admin/pointage',
      content: Scaffold(
        backgroundColor: bgColor,
        body: Column(
          children: [
            // Header
            Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: isDark 
                    ? [const Color(0xFF1E293B), const Color(0xFF0F172A)]
                    : [const Color(0xFF667EEA), const Color(0xFF764BA2)],
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      const Icon(CupertinoIcons.clock_fill, color: Colors.white, size: 28),
                      const SizedBox(width: 12),
                      const Text(
                        'Gestion des Pointages',
                        style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: Colors.white),
                      ),
                      const Spacer(),
                      // Pending count badge
                      if (_pendingEntries.isNotEmpty)
                        Container(
                          margin: const EdgeInsets.only(right: 12),
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                          decoration: BoxDecoration(
                            color: Colors.orange.withOpacity(0.2),
                            borderRadius: BorderRadius.circular(20),
                            border: Border.all(color: Colors.orange.withOpacity(0.5)),
                          ),
                          child: Row(
                            children: [
                              const Icon(CupertinoIcons.exclamationmark_circle, color: Colors.orange, size: 16),
                              const SizedBox(width: 6),
                              Text(
                                '${_pendingEntries.length} en attente',
                                style: const TextStyle(color: Colors.orange, fontWeight: FontWeight.w600, fontSize: 12),
                              ),
                            ],
                          ),
                        ),
                      // Active count badge
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                        decoration: BoxDecoration(
                          color: Colors.green.withOpacity(0.2),
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(color: Colors.green.withOpacity(0.5)),
                        ),
                        child: Row(
                          children: [
                            Container(
                              width: 8,
                              height: 8,
                              decoration: const BoxDecoration(color: Colors.green, shape: BoxShape.circle),
                            ),
                            const SizedBox(width: 8),
                            Text(
                              '${_activeUsers.length} actif(s)',
                              style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  // Tabs - 4 tabs now
                  TabBar(
                    controller: _tabController,
                    indicatorColor: Colors.white,
                    labelColor: Colors.white,
                    unselectedLabelColor: Colors.white60,
                    isScrollable: true,
                    tabs: [
                      const Tab(icon: Icon(CupertinoIcons.person_2_fill), text: 'En cours'),
                      Tab(
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(CupertinoIcons.clock),
                            const SizedBox(width: 8),
                            const Text('En attente'),
                            if (_pendingEntries.isNotEmpty) ...[
                              const SizedBox(width: 6),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                decoration: BoxDecoration(
                                  color: Colors.orange,
                                  borderRadius: BorderRadius.circular(10),
                                ),
                                child: Text(
                                  '${_pendingEntries.length}',
                                  style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold),
                                ),
                              ),
                            ],
                          ],
                        ),
                      ),
                      const Tab(icon: Icon(CupertinoIcons.calendar), text: 'Historique'),
                      const Tab(icon: Icon(CupertinoIcons.settings), text: 'Paramètres'),
                    ],
                  ),
                ],
              ),
            ),

            // Tab Content
            Expanded(
              child: TabBarView(
                controller: _tabController,
                children: [
                  _buildActiveTab(cardColor, textColor),
                  _buildPendingTab(cardColor, textColor),
                  _buildHistoryTab(cardColor, textColor),
                  _buildSettingsTab(cardColor, textColor),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  /// Tab 1: Active Users (currently clocked in)
  Widget _buildActiveTab(Color cardColor, Color textColor) {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_activeUsers.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(CupertinoIcons.moon_zzz, size: 64, color: textColor.withOpacity(0.3)),
            const SizedBox(height: 16),
            Text('Aucun employé pointé', style: TextStyle(fontSize: 18, color: textColor.withOpacity(0.6))),
            const SizedBox(height: 8),
            Text('Personne n\'est actuellement au travail', style: TextStyle(color: textColor.withOpacity(0.4))),
          ],
        ),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(24),
      itemCount: _activeUsers.length,
      itemBuilder: (context, index) {
        final user = _activeUsers[index];
        final mins = int.tryParse(user['duree_minutes']?.toString() ?? '0') ?? 0;
        final hours = mins ~/ 60;
        final remainingMins = mins % 60;
        final duration = '${hours}h${remainingMins.toString().padLeft(2, '0')}';

        return Container(
          margin: const EdgeInsets.only(bottom: 12),
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: cardColor,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: Colors.green.withOpacity(0.3)),
            boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 10)],
          ),
          child: Row(
            children: [
              // Avatar
              Container(
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  color: MacOSTheme.successGreen.withOpacity(0.2),
                  borderRadius: BorderRadius.circular(24),
                ),
                child: const Center(child: Icon(CupertinoIcons.person_fill, color: MacOSTheme.successGreen)),
              ),
              const SizedBox(width: 16),
              // Info
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      user['full_name'] ?? 'Inconnu',
                      style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600, color: textColor),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'Arrivée: ${user['heure_arrivee'] ?? '-'}',
                      style: TextStyle(color: textColor.withOpacity(0.6), fontSize: 13),
                    ),
                  ],
                ),
              ),
              // Duration
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  color: MacOSTheme.successGreen.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  duration,
                  style: const TextStyle(color: MacOSTheme.successGreen, fontWeight: FontWeight.bold, fontSize: 16),
                ),
              ),
              const SizedBox(width: 12),
              // Force clock out button
              CupertinoButton(
                padding: EdgeInsets.zero,
                child: Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: Colors.orange.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Icon(CupertinoIcons.xmark_circle, color: Colors.orange, size: 20),
                ),
                onPressed: () => _forceClockOut(user),
              ),
            ],
          ),
        );
      },
    );
  }

  /// Tab 2: Pending entries (awaiting admin approval)
  Widget _buildPendingTab(Color cardColor, Color textColor) {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_pendingEntries.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(CupertinoIcons.checkmark_seal, size: 64, color: textColor.withOpacity(0.3)),
            const SizedBox(height: 16),
            Text('Aucun pointage en attente', style: TextStyle(fontSize: 18, color: textColor.withOpacity(0.6))),
            const SizedBox(height: 8),
            Text('Tous les pointages sont validés', style: TextStyle(color: textColor.withOpacity(0.4))),
          ],
        ),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(24),
      itemCount: _pendingEntries.length,
      itemBuilder: (context, index) {
        final entry = _pendingEntries[index];
        
        return Container(
          margin: const EdgeInsets.only(bottom: 12),
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: cardColor,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: Colors.orange.withOpacity(0.3)),
            boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 10)],
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  // Avatar
                  Container(
                    width: 40,
                    height: 40,
                    decoration: BoxDecoration(
                      color: Colors.orange.withOpacity(0.2),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: const Center(child: Icon(CupertinoIcons.person_fill, color: Colors.orange, size: 20)),
                  ),
                  const SizedBox(width: 12),
                  // Name
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          entry['employe_nom'] ?? 'Inconnu',
                          style: TextStyle(fontSize: 15, fontWeight: FontWeight.w600, color: textColor),
                        ),
                        Text(
                          entry['date_pointage'] ?? '',
                          style: TextStyle(fontSize: 12, color: textColor.withOpacity(0.5)),
                        ),
                      ],
                    ),
                  ),
                  // Time badges
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: MacOSTheme.successGreen.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Text(
                      entry['heure_arrivee'] ?? '-',
                      style: const TextStyle(color: MacOSTheme.successGreen, fontWeight: FontWeight.w600, fontSize: 12),
                    ),
                  ),
                  if (entry['heure_depart'] != null) ...[
                    const SizedBox(width: 4),
                    const Icon(CupertinoIcons.arrow_right, size: 12, color: Colors.grey),
                    const SizedBox(width: 4),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: MacOSTheme.dangerRed.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        entry['heure_depart'] ?? '-',
                        style: const TextStyle(color: MacOSTheme.dangerRed, fontWeight: FontWeight.w600, fontSize: 12),
                      ),
                    ),
                  ],
                  const SizedBox(width: 8),
                  Text(
                    entry['duree_formatee'] ?? '',
                    style: TextStyle(color: textColor.withOpacity(0.6), fontSize: 12),
                  ),
                ],
              ),
              // Reason
              if (entry['approval_reason'] != null && (entry['approval_reason'] as String).isNotEmpty) ...[
                const SizedBox(height: 8),
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: Colors.orange.withOpacity(0.05),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Row(
                    children: [
                      const Icon(CupertinoIcons.info_circle, size: 14, color: Colors.orange),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          entry['approval_reason'] ?? '',
                          style: TextStyle(fontSize: 12, color: textColor.withOpacity(0.7)),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
              const SizedBox(height: 12),
              // Action buttons
              Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  // Edit/Correct button
                  OutlinedButton.icon(
                    onPressed: () => _editEntry(entry),
                    icon: const Icon(CupertinoIcons.pencil, size: 16),
                    label: const Text('Corriger'),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: MacOSTheme.accentBlue,
                      side: const BorderSide(color: MacOSTheme.accentBlue),
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    ),
                  ),
                  const SizedBox(width: 8),
                  // Reject button
                  OutlinedButton.icon(
                    onPressed: () => _rejectEntry(entry),
                    icon: const Icon(CupertinoIcons.xmark, size: 16),
                    label: const Text('Rejeter'),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: MacOSTheme.dangerRed,
                      side: const BorderSide(color: MacOSTheme.dangerRed),
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    ),
                  ),
                  const SizedBox(width: 8),
                  // Approve button
                  ElevatedButton.icon(
                    onPressed: () => _approveEntry(entry),
                    icon: const Icon(CupertinoIcons.checkmark_alt, size: 16),
                    label: const Text('Approuver'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: MacOSTheme.successGreen,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    ),
                  ),
                ],
              ),
            ],
          ),
        );
      },
    );
  }

  /// Tab 3: History (daily entries)
  Widget _buildHistoryTab(Color cardColor, Color textColor) {
    final dateStr = DateFormat('dd/MM/yyyy').format(_selectedDate);

    return Column(
      children: [
        // Date picker
        Container(
          padding: const EdgeInsets.all(16),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              CupertinoButton(
                padding: EdgeInsets.zero,
                child: const Icon(CupertinoIcons.chevron_left, color: MacOSTheme.accentBlue),
                onPressed: () {
                  setState(() => _selectedDate = _selectedDate.subtract(const Duration(days: 1)));
                  _loadData();
                },
              ),
              const SizedBox(width: 16),
              GestureDetector(
                onTap: () async {
                  final picked = await showDatePicker(
                    context: context,
                    initialDate: _selectedDate,
                    firstDate: DateTime(2020),
                    lastDate: DateTime.now(),
                  );
                  if (picked != null) {
                    setState(() => _selectedDate = picked);
                    _loadData();
                  }
                },
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                  decoration: BoxDecoration(
                    color: cardColor,
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: MacOSTheme.accentBlue.withOpacity(0.3)),
                  ),
                  child: Row(
                    children: [
                      const Icon(CupertinoIcons.calendar, size: 18, color: MacOSTheme.accentBlue),
                      const SizedBox(width: 8),
                      Text(dateStr, style: TextStyle(fontWeight: FontWeight.w600, color: textColor)),
                    ],
                  ),
                ),
              ),
              const SizedBox(width: 16),
              CupertinoButton(
                padding: EdgeInsets.zero,
                child: const Icon(CupertinoIcons.chevron_right, color: MacOSTheme.accentBlue),
                onPressed: () {
                  setState(() => _selectedDate = _selectedDate.add(const Duration(days: 1)));
                  _loadData();
                },
              ),
            ],
          ),
        ),

        // Entries list
        Expanded(
          child: _isLoading
            ? const Center(child: CircularProgressIndicator())
            : _dailyEntries.isEmpty
              ? Center(
                  child: Text('Aucun pointage ce jour', style: TextStyle(color: textColor.withOpacity(0.5))),
                )
              : ListView.builder(
                  padding: const EdgeInsets.symmetric(horizontal: 24),
                  itemCount: _dailyEntries.length,
                  itemBuilder: (context, index) {
                    final entry = _dailyEntries[index];
                    final isApproved = entry['auto_approved'] == 1 || entry['admin_approved'] == 1;
                    final status = entry['status'] ?? 'active';
                    
                    return Container(
                      margin: const EdgeInsets.only(bottom: 12),
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: cardColor,
                        borderRadius: BorderRadius.circular(12),
                        border: status == 'rejected' 
                          ? Border.all(color: MacOSTheme.dangerRed.withOpacity(0.3))
                          : null,
                        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 10)],
                      ),
                      child: Column(
                        children: [
                          Row(
                            children: [
                              Icon(
                                status == 'rejected' 
                                  ? CupertinoIcons.xmark_seal_fill
                                  : isApproved 
                                    ? CupertinoIcons.checkmark_seal_fill 
                                    : CupertinoIcons.clock_fill,
                                color: status == 'rejected'
                                  ? MacOSTheme.dangerRed
                                  : isApproved ? MacOSTheme.successGreen : Colors.orange,
                              ),
                              const SizedBox(width: 16),
                              Expanded(
                                child: Text(
                                  entry['employe_nom'] ?? 'Inconnu',
                                  style: TextStyle(fontWeight: FontWeight.w600, color: textColor),
                                ),
                              ),
                              // Arrivée
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                decoration: BoxDecoration(
                                  color: MacOSTheme.successGreen.withOpacity(0.1),
                                  borderRadius: BorderRadius.circular(6),
                                ),
                                child: Text(
                                  entry['heure_arrivee'] ?? '-',
                                  style: const TextStyle(color: MacOSTheme.successGreen, fontWeight: FontWeight.w600),
                                ),
                              ),
                              const SizedBox(width: 8),
                              const Icon(CupertinoIcons.arrow_right, size: 14, color: Colors.grey),
                              const SizedBox(width: 8),
                              // Départ
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                decoration: BoxDecoration(
                                  color: (entry['heure_depart'] != null) 
                                    ? MacOSTheme.dangerRed.withOpacity(0.1)
                                    : Colors.orange.withOpacity(0.1),
                                  borderRadius: BorderRadius.circular(6),
                                ),
                                child: Text(
                                  entry['heure_depart'] ?? 'En cours',
                                  style: TextStyle(
                                    color: (entry['heure_depart'] != null) ? MacOSTheme.dangerRed : Colors.orange,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ),
                              const SizedBox(width: 16),
                              // Duration
                              Text(
                                entry['duree_formatee'] ?? '',
                                style: TextStyle(color: textColor.withOpacity(0.6), fontSize: 13),
                              ),
                            ],
                          ),
                          const SizedBox(height: 12),
                          // Action buttons row
                          Row(
                            mainAxisAlignment: MainAxisAlignment.end,
                            children: [
                              // Edit button
                              OutlinedButton.icon(
                                onPressed: () => _editEntry(entry),
                                icon: const Icon(CupertinoIcons.pencil, size: 14),
                                label: const Text('Corriger'),
                                style: OutlinedButton.styleFrom(
                                  foregroundColor: MacOSTheme.accentBlue,
                                  side: const BorderSide(color: MacOSTheme.accentBlue),
                                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                                  textStyle: const TextStyle(fontSize: 12),
                                ),
                              ),
                              const SizedBox(width: 8),
                              // Reject button
                              if (!isApproved && status != 'rejected')
                                OutlinedButton.icon(
                                  onPressed: () => _rejectEntry(entry),
                                  icon: const Icon(CupertinoIcons.xmark, size: 14),
                                  label: const Text('Rejeter'),
                                  style: OutlinedButton.styleFrom(
                                    foregroundColor: MacOSTheme.dangerRed,
                                    side: const BorderSide(color: MacOSTheme.dangerRed),
                                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                                    textStyle: const TextStyle(fontSize: 12),
                                  ),
                                ),
                              if (!isApproved && status != 'rejected')
                                const SizedBox(width: 8),
                              // Approve button
                              if (!isApproved && status != 'rejected')
                                ElevatedButton.icon(
                                  onPressed: () => _approveEntry(entry),
                                  icon: const Icon(CupertinoIcons.checkmark_alt, size: 14),
                                  label: const Text('Approuver'),
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: MacOSTheme.successGreen,
                                    foregroundColor: Colors.white,
                                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                                    textStyle: const TextStyle(fontSize: 12),
                                  ),
                                ),
                            ],
                          ),
                        ],
                      ),
                    );
                  },
                ),
        ),
      ],
    );
  }

  /// Tab 4: Settings (time slots configuration)
  Widget _buildSettingsTab(Color cardColor, Color textColor) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Paramètres des Créneaux Horaires',
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: textColor),
          ),
          const SizedBox(height: 8),
          Text(
            'Les pointages effectués dans les créneaux autorisés sont automatiquement approuvés.',
            style: TextStyle(color: textColor.withOpacity(0.6)),
          ),
          const SizedBox(height: 24),

          // Global slots card
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: cardColor,
              borderRadius: BorderRadius.circular(16),
              boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 10)],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    const Icon(CupertinoIcons.globe, color: MacOSTheme.accentBlue),
                    const SizedBox(width: 12),
                    Text('Créneaux Globaux', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600, color: textColor)),
                  ],
                ),
                const SizedBox(height: 20),
                Row(
                  children: [
                    Expanded(child: _buildTimeSlotField('Matin - Début', '08:00', textColor, cardColor)),
                    const SizedBox(width: 16),
                    Expanded(child: _buildTimeSlotField('Matin - Fin', '12:30', textColor, cardColor)),
                  ],
                ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    Expanded(child: _buildTimeSlotField('Après-midi - Début', '13:30', textColor, cardColor)),
                    const SizedBox(width: 16),
                    Expanded(child: _buildTimeSlotField('Après-midi - Fin', '18:30', textColor, cardColor)),
                  ],
                ),
                const SizedBox(height: 20),
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: MacOSTheme.accentBlue.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Row(
                    children: [
                      const Icon(CupertinoIcons.info_circle, color: MacOSTheme.accentBlue, size: 18),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Text(
                          'Si un employé a un planning (employee_schedules), celui-ci sera utilisé pour l\'auto-approbation.',
                          style: TextStyle(fontSize: 12, color: textColor.withOpacity(0.7)),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTimeSlotField(String label, String defaultValue, Color textColor, Color cardColor) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: TextStyle(fontSize: 12, color: textColor.withOpacity(0.6))),
        const SizedBox(height: 6),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
          decoration: BoxDecoration(
            color: cardColor,
            borderRadius: BorderRadius.circular(8),
            border: Border.all(color: textColor.withOpacity(0.1)),
          ),
          child: Row(
            children: [
              const Icon(CupertinoIcons.time, size: 16, color: Colors.grey),
              const SizedBox(width: 8),
              Text(defaultValue, style: TextStyle(color: textColor)),
            ],
          ),
        ),
      ],
    );
  }

  Future<void> _forceClockOut(Map<String, dynamic> user) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Forcer le départ'),
        content: Text('Voulez-vous forcer le départ de ${user['full_name']} ?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Annuler')),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style: ElevatedButton.styleFrom(backgroundColor: Colors.orange),
            child: const Text('Confirmer'),
          ),
        ],
      ),
    );

    if (confirm == true) {
      try {
        final api = context.read<AuthService>().getApiService();
        await api.post('/admin/pointage/force_out.php', {
          'user_id': user['user_id'],
        });
        
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Départ forcé enregistré'), backgroundColor: Colors.orange),
          );
          _loadData(); // Reload to refresh list
        }
      } catch (e) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red),
          );
        }
      }
    }
  }

  Future<void> _approveEntry(Map<String, dynamic> entry) async {
    // Calculate latency if approval_reason contains timing info
    final approvalReason = entry['approval_reason']?.toString() ?? '';
    int latencyMinutes = 0;
    
    // Try to extract latency from reason or calculate from expected time
    if (approvalReason.contains('hors')) {
      // Parse expected time vs actual time if available
      final regExp = RegExp(r'Réel: (\d{2}):(\d{2})');
      final match = regExp.firstMatch(approvalReason);
      if (match != null) {
        // Rough estimate - real calculation would need expected time
        latencyMinutes = 15; // Default estimate
      }
    }
    
    bool recordLatency = true;
    
    final result = await showDialog<Map<String, dynamic>>(
      context: context,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setState) {
            return AlertDialog(
              title: Row(
                children: [
                  const Icon(CupertinoIcons.clock, color: Colors.orange),
                  const SizedBox(width: 12),
                  const Text('Approuver le pointage'),
                ],
              ),
              content: SizedBox(
                width: 380,
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Employé: ${entry['employe_nom'] ?? 'Inconnu'}',
                      style: const TextStyle(fontWeight: FontWeight.w600),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'Arrivée: ${entry['heure_arrivee'] ?? '-'} → Départ: ${entry['heure_depart'] ?? 'En cours'}',
                      style: TextStyle(color: Colors.grey[600]),
                    ),
                    if (approvalReason.isNotEmpty) ...[
                      const SizedBox(height: 12),
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: Colors.orange.withOpacity(0.1),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Row(
                          children: [
                            const Icon(CupertinoIcons.exclamationmark_triangle, size: 16, color: Colors.orange),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                approvalReason,
                                style: const TextStyle(fontSize: 12, color: Colors.orange),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                    const SizedBox(height: 20),
                    TextField(
                      decoration: const InputDecoration(
                        labelText: 'Durée du retard (minutes)',
                        prefixIcon: Icon(CupertinoIcons.time, color: Colors.orange),
                        border: OutlineInputBorder(),
                      ),
                      keyboardType: TextInputType.number,
                      controller: TextEditingController(text: latencyMinutes.toString()),
                      onChanged: (v) => latencyMinutes = int.tryParse(v) ?? 0,
                    ),
                    const SizedBox(height: 16),
                    CheckboxListTile(
                      value: recordLatency,
                      onChanged: (v) => setState(() => recordLatency = v ?? true),
                      title: const Text('Enregistrer le retard dans le planning', style: TextStyle(fontSize: 14)),
                      subtitle: const Text('Créera une entrée dans presence_events', style: TextStyle(fontSize: 11)),
                      controlAffinity: ListTileControlAffinity.leading,
                      contentPadding: EdgeInsets.zero,
                      activeColor: MacOSTheme.accentBlue,
                    ),
                  ],
                ),
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.pop(context),
                  child: const Text('Annuler'),
                ),
                ElevatedButton.icon(
                  onPressed: () => Navigator.pop(context, {
                    'latency_minutes': latencyMinutes,
                    'record_latency': recordLatency,
                  }),
                  icon: const Icon(CupertinoIcons.checkmark_alt, size: 16),
                  label: const Text('Approuver'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: MacOSTheme.successGreen,
                    foregroundColor: Colors.white,
                  ),
                ),
              ],
            );
          },
        );
      },
    );

    if (result != null) {
      try {
        final api = context.read<AuthService>().getApiService();
        await api.post('/admin/pointage/approve.php', {
          'entry_id': entry['id'],
          'latency_minutes': result['latency_minutes'],
          'record_latency': result['record_latency'] == true,
        });
        
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Pointage approuvé ✅'), backgroundColor: MacOSTheme.successGreen),
        );
        _loadData();
      } catch (e) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erreur: $e'), backgroundColor: MacOSTheme.dangerRed),
        );
      }
    }
  }

  Future<void> _rejectEntry(Map<String, dynamic> entry) async {
    final reason = await showDialog<String>(
      context: context,
      builder: (context) {
        String reasonText = '';
        return AlertDialog(
          title: const Text('Rejeter le pointage'),
          content: TextField(
            onChanged: (v) => reasonText = v,
            decoration: const InputDecoration(
              hintText: 'Raison du rejet (optionnel)',
              border: OutlineInputBorder(),
            ),
          ),
          actions: [
            TextButton(onPressed: () => Navigator.pop(context), child: const Text('Annuler')),
            ElevatedButton(
              onPressed: () => Navigator.pop(context, reasonText),
              style: ElevatedButton.styleFrom(backgroundColor: MacOSTheme.dangerRed),
              child: const Text('Rejeter'),
            ),
          ],
        );
      },
    );

    if (reason != null) {
      try {
        final api = context.read<AuthService>().getApiService();
        await api.post('/admin/pointage/reject.php', {'entry_id': entry['id'], 'reason': reason});
        
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Pointage rejeté'), backgroundColor: Colors.orange),
        );
        _loadData();
      } catch (e) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erreur: $e'), backgroundColor: MacOSTheme.dangerRed),
        );
      }
    }
  }

  Future<void> _editEntry(Map<String, dynamic> entry) async {
    final arriveController = TextEditingController(text: entry['heure_arrivee'] ?? '');
    final departController = TextEditingController(text: entry['heure_depart'] ?? '');
    
    final result = await showDialog<Map<String, String>>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: Row(
            children: [
              const Icon(CupertinoIcons.pencil, color: MacOSTheme.accentBlue),
              const SizedBox(width: 12),
              const Text('Corriger le pointage'),
            ],
          ),
          content: SizedBox(
            width: 350,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Employé: ${entry['employe_nom'] ?? 'Inconnu'}',
                  style: const TextStyle(fontWeight: FontWeight.w600),
                ),
                const SizedBox(height: 20),
                TextField(
                  controller: arriveController,
                  decoration: const InputDecoration(
                    labelText: 'Heure d\'arrivée',
                    hintText: 'HH:MM (ex: 09:00)',
                    prefixIcon: Icon(CupertinoIcons.arrow_right_circle, color: MacOSTheme.successGreen),
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 16),
                TextField(
                  controller: departController,
                  decoration: const InputDecoration(
                    labelText: 'Heure de départ',
                    hintText: 'HH:MM (ex: 18:00) ou vide',
                    prefixIcon: Icon(CupertinoIcons.arrow_left_circle, color: MacOSTheme.dangerRed),
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 12),
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: MacOSTheme.accentBlue.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Row(
                    children: [
                      Icon(CupertinoIcons.info_circle, size: 16, color: MacOSTheme.accentBlue),
                      SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          'Format: HH:MM (24h). Laissez départ vide si en cours.',
                          style: TextStyle(fontSize: 11, color: MacOSTheme.accentBlue),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Annuler'),
            ),
            ElevatedButton.icon(
              onPressed: () => Navigator.pop(context, {
                'arrivee': arriveController.text,
                'depart': departController.text,
              }),
              icon: const Icon(CupertinoIcons.checkmark_alt, size: 16),
              label: const Text('Sauvegarder'),
              style: ElevatedButton.styleFrom(
                backgroundColor: MacOSTheme.accentBlue,
                foregroundColor: Colors.white,
              ),
            ),
          ],
        );
      },
    );

    if (result != null) {
      try {
        final api = context.read<AuthService>().getApiService();
        await api.post('/admin/pointage/edit.php', {
          'entry_id': entry['id'],
          'clock_in': result['arrivee'],
          'clock_out': result['depart'],
        });
        
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Pointage corrigé ✅'), backgroundColor: MacOSTheme.accentBlue),
        );
        _loadData();
      } catch (e) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erreur: $e'), backgroundColor: MacOSTheme.dangerRed),
        );
      }
    }
  }
}
