import 'package:flutter/material.dart';

class FormationCard extends StatelessWidget {
  final Map<String, dynamic> formation;
  final VoidCallback onTap;

  const FormationCard({
    super.key,
    required this.formation,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final bool available = formation['disponible'] == true;
    final String colorHex = formation['couleur'] ?? '#3b82f6';
    final Color mainColor = _parseColor(colorHex);

    return MouseRegion(
      cursor: available ? SystemMouseCursors.click : SystemMouseCursors.forbidden,
      child: GestureDetector(
        onTap: available ? onTap : null,
        child: Container(
          decoration: BoxDecoration(
            color: Colors.white, // In dark mode context this might need adjustment, but web design used white/dark specific
            gradient: LinearGradient(
               begin: Alignment.topLeft,
               end: Alignment.bottomRight,
                colors: [
                  const Color(0xFF1E293B),
                  const Color(0xFF0F172A),
                ]
            ),
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: Colors.white.withOpacity(0.05)),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(0.2),
                blurRadius: 10,
                offset: const Offset(0, 4),
              ),
            ],
          ),
          child: Stack(
            children: [
              Padding(
                padding: const EdgeInsets.all(20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Icon
                    Container(
                      width: 60,
                      height: 60,
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          colors: [mainColor, mainColor.withOpacity(0.7)],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                        borderRadius: BorderRadius.circular(16),
                        boxShadow: [
                          BoxShadow(
                            color: mainColor.withOpacity(0.3),
                            blurRadius: 10,
                            offset: const Offset(0, 4),
                          ),
                        ],
                      ),
                      child: Center(
                        child: Icon(_getIconData(formation['icone']), color: Colors.white, size: 28),
                      ),
                    ),
                    const SizedBox(height: 16),
                    
                    // Title
                    Text(
                      formation['titre'] ?? 'Formation',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    
                    const SizedBox(height: 8),
                    
                    // Description
                    Text(
                      formation['description'] ?? '',
                      style: TextStyle(color: Colors.grey[400], fontSize: 13, height: 1.4),
                      maxLines: 3,
                      overflow: TextOverflow.ellipsis,
                    ),
                    
                    const Spacer(),
                    
                    // Meta Data
                    Wrap(
                      spacing: 12,
                      runSpacing: 8,
                      children: [
                        _buildMetaItem(Icons.access_time, formation['duree'] ?? 'N/A'),
                        _buildMetaItem(Icons.format_list_numbered, '${formation['etapes']} étapes'),
                      ],
                    ),
                    
                    const SizedBox(height: 12),
                    
                    // Level Badge
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: _getLevelColor(formation['niveau']).withOpacity(0.15),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Text(
                        formation['niveau'] ?? 'Niveau',
                        style: TextStyle(
                          color: _getLevelColor(formation['niveau']),
                          fontSize: 11,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              
              if (!available)
                Positioned(
                  top: 16,
                  right: 16,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(colors: [Color(0xFFF59E0B), Color(0xFFD97706)]),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: const Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.lock, color: Colors.white, size: 12),
                        SizedBox(width: 4),
                        Text(
                          'Bientôt',
                          style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
                        ),
                      ],
                    ),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildMetaItem(IconData icon, String text) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 14, color: Colors.grey[500]),
        const SizedBox(width: 4),
        Text(
          text,
          style: TextStyle(color: Colors.grey[500], fontSize: 12),
        ),
      ],
    );
  }

  Color _parseColor(String? hexString) {
    if (hexString == null || hexString.isEmpty) return Colors.blue;
    try {
      final buffer = StringBuffer();
      if (hexString.length == 6 || hexString.length == 7) buffer.write('ff');
      buffer.write(hexString.replaceFirst('#', ''));
      return Color(int.parse(buffer.toString(), radix: 16));
    } catch (e) {
      return Colors.blue;
    }
  }

  Color _getLevelColor(String? level) {
    switch (level?.toLowerCase()) {
      case 'débutant':
      case 'debutant':
        return const Color(0xFF10B981);
      case 'intermédiaire':
      case 'intermediaire':
        return const Color(0xFFF59E0B);
      case 'avancé':
      case 'avance':
      case 'expert':
        return const Color(0xFFEF4444);
      default:
        return const Color(0xFF3B82F6);
    }
  }

  IconData _getIconData(String? iconName) {
    switch (iconName) {
      case 'fa-wrench': return Icons.build;
      case 'fa-tasks': return Icons.task;
      case 'fa-shopping-cart': return Icons.shopping_cart;
      case 'fa-clock': return Icons.access_time;
      case 'fa-hand-holding-usd': return Icons.attach_money;
      case 'fa-book': return Icons.menu_book;
      case 'fa-lightbulb': return Icons.lightbulb;
      case 'fa-boxes': return Icons.inventory_2;
      case 'fa-user-shield': return Icons.admin_panel_settings;
      case 'fa-flag': return Icons.flag;
      case 'fa-users': return Icons.people;
      case 'fa-desktop': return Icons.desktop_mac;
      case 'fa-handshake': return Icons.handshake;
      case 'fa-chart-line': return Icons.show_chart;
      default: return Icons.school;
    }
  }
}
