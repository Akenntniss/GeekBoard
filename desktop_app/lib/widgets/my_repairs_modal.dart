import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../theme/macos_theme.dart';
import '../screens/reparations/repair_detail_modal.dart';

class MyRepairsModal extends StatefulWidget {
  final ApiService apiService;

  const MyRepairsModal({Key? key, required this.apiService}) : super(key: key);

  @override
  State<MyRepairsModal> createState() => _MyRepairsModalState();
}

class _MyRepairsModalState extends State<MyRepairsModal> {
  bool _isLoading = true;
  List<dynamic> _repairs = [];
  String _error = '';

  @override
  void initState() {
    super.initState();
    _loadMyRepairs();
  }

  Future<void> _loadMyRepairs() async {
    setState(() => _isLoading = true);
    try {
      // Accessing partial path relative to api/v2
      final response = await widget.apiService.get('/../../pages/get_my_repairs.php');
      if (mounted) {
        setState(() {
          _repairs = response['repairs'] ?? [];
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

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    return Dialog(
      backgroundColor: Colors.transparent,
      insetPadding: const EdgeInsets.all(24),
      child: Container(
        width: 800,
        height: 600,
        decoration: BoxDecoration(
          color: Theme.of(context).dialogTheme.backgroundColor ?? Theme.of(context).cardColor,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: Theme.of(context).dividerColor),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(isDark ? 0.5 : 0.1),
              blurRadius: 20,
              offset: const Offset(0, 10),
            ),
          ],
        ),
        child: Column(
          children: [
            // Header
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: isDark ? Colors.white.withOpacity(0.05) : Colors.black.withOpacity(0.03),
                borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
                border: Border(bottom: BorderSide(color: Theme.of(context).dividerColor)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.handyman, color: MacOSTheme.accentBlue),
                  const SizedBox(width: 12),
                  Text(
                    "Mes Réparations",
                    style: TextStyle(
                      color: Theme.of(context).textTheme.titleLarge?.color,
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const Spacer(),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: MacOSTheme.accentBlue.withOpacity(0.2),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      "${_repairs.length} active(s)",
                      style: const TextStyle(color: MacOSTheme.accentBlue, fontWeight: FontWeight.bold),
                    ),
                  ),
                  const SizedBox(width: 16),
                  IconButton(
                    icon: Icon(Icons.close, color: Theme.of(context).iconTheme.color),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),
            ),

            // Content
            Expanded(
              child: _isLoading
                  ? const Center(child: CircularProgressIndicator())
                  : _error.isNotEmpty
                      ? Center(child: Text("Erreur: $_error", style: const TextStyle(color: Colors.red)))
                      : _repairs.isEmpty
                          ? Center(
                              child: Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  const Icon(Icons.check_circle_outline, size: 64, color: Colors.grey),
                                  const SizedBox(height: 16),
                                  Text(
                                    "Aucune réparation active attribuée",
                                    style: TextStyle(color: Theme.of(context).textTheme.bodyMedium?.color, fontSize: 16),
                                  ),
                                ],
                              ),
                            )
                          : ListView.separated(
                              padding: const EdgeInsets.all(16),
                              itemCount: _repairs.length,
                              separatorBuilder: (_, __) => const SizedBox(height: 12),
                              itemBuilder: (context, index) {
                                final repair = _repairs[index];
                                final isUrgent = repair['is_urgent'] == true;
                                return _buildRepairItem(repair, isUrgent);
                              },
                            ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildRepairItem(dynamic repair, bool isUrgent) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    return Container(
      decoration: BoxDecoration(
        color: isDark ? Colors.white.withOpacity(0.05) : Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: isUrgent ? MacOSTheme.dangerRed : Theme.of(context).dividerColor, 
          width: 1
        ),
        boxShadow: isDark ? null : [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: ListTile(
        contentPadding: const EdgeInsets.all(12),
        leading: Container(
          width: 48,
          height: 48,
          decoration: BoxDecoration(
            color: isUrgent ? MacOSTheme.dangerRed.withOpacity(0.2) : MacOSTheme.accentBlue.withOpacity(0.1),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Icon(
            Icons.smartphone,
            color: isUrgent ? MacOSTheme.dangerRed : MacOSTheme.accentBlue,
          ),
        ),
        title: Row(
          children: [
            Expanded(
              child: Text(
                "${repair['marque'] ?? ''} ${repair['modele'] ?? ''}",
                style: TextStyle(color: Theme.of(context).textTheme.bodyLarge?.color, fontWeight: FontWeight.bold),
                overflow: TextOverflow.ellipsis,
              ),
            ),
            if (isUrgent) ...[
              const SizedBox(width: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                decoration: BoxDecoration(
                  color: MacOSTheme.dangerRed,
                  borderRadius: BorderRadius.circular(4),
                ),
                child: const Text("URGENT", style: TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold)),
              ),
            ],
          ],
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const SizedBox(height: 4),
            Text(
              "${repair['client_nom']} ${repair['client_prenom']} - ${repair['client_telephone']}",
              style: TextStyle(color: isDark ? Colors.grey : Colors.grey[600]),
            ),
            const SizedBox(height: 4),
            Text(
              repair['description_probleme'] ?? '',
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(color: Theme.of(context).textTheme.bodyMedium?.color?.withOpacity(0.7)),
            ),
          ],
        ),
        trailing: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Text("#${repair['id']}", style: const TextStyle(color: Colors.grey, fontWeight: FontWeight.bold)),
            const SizedBox(height: 4),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(
                color: _getStatusColor(repair['statut_couleur']).withOpacity(0.2),
                borderRadius: BorderRadius.circular(6),
              ),
              child: Text(
                repair['statut_nom'] ?? repair['statut'] ?? 'Inconnu',
                style: TextStyle(color: _getStatusColor(repair['statut_couleur']), fontSize: 11),
              ),
            ),
          ],
        ),
        onTap: () {
          showDialog(
            context: context,
            builder: (_) => RepairDetailModal(
              repair: Map<String, dynamic>.from(repair),
              apiService: widget.apiService,
              onUpdate: _loadMyRepairs,
            ),
          );
        },
      ),
    );
  }

  Color _getStatusColor(String? colorCode) {
    if (colorCode == 'info') return MacOSTheme.accentBlue;
    if (colorCode == 'warning') return MacOSTheme.warningOrange;
    if (colorCode == 'success') return MacOSTheme.successGreen;
    if (colorCode == 'danger') return MacOSTheme.dangerRed;
    return Colors.grey;
  }
}
