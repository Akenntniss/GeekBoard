import 'package:flutter/material.dart';
import '../../theme/macos_theme.dart';
import '../../services/api_service.dart';

/// Overlay qui s'affiche pendant le drag d'une carte de réparation
/// Contient des zones de dépôt pour chaque catégorie de statut
class StatusDropOverlay extends StatelessWidget {
  final Function(Map<String, dynamic> repair, String categoryId) onDrop;
  
  const StatusDropOverlay({
    super.key,
    required this.onDrop,
  });

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    return Container(
      color: Colors.black.withOpacity(0.3),
      child: Center(
        child: Padding(
          padding: const EdgeInsets.all(40),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              // Titre
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
                decoration: BoxDecoration(
                  color: isDark ? Colors.grey[900] : Colors.white,
                  borderRadius: BorderRadius.circular(16),
                ),
                child: const Text(
                  'Glissez la carte vers une catégorie',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                  textAlign: TextAlign.center,
                ),
              ),
              
              const SizedBox(height: 32),
              
              // Zones de dépôt en grille 2x2
              Expanded(
                child: GridView.count(
                  crossAxisCount: 2,
                  mainAxisSpacing: 16,
                  crossAxisSpacing: 16,
                  shrinkWrap: true,
                  children: [
                    _buildDropZone(
                      context,
                      '3',
                      'En Attente',
                      Icons.schedule,
                      MacOSTheme.warningOrange,
                      isDark,
                    ),
                    _buildDropZone(
                      context,
                      '2',
                      'En Cours',
                      Icons.build_circle,
                      MacOSTheme.accentBlue,
                      isDark,
                    ),
                    _buildDropZone(
                      context,
                      '4',
                      'Terminé',
                      Icons.check_circle,
                      MacOSTheme.successGreen,
                      isDark,
                    ),
                    _buildDropZone(
                      context,
                      '5',
                      'Annulé',
                      Icons.cancel,
                      MacOSTheme.dangerRed,
                      isDark,
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildDropZone(
    BuildContext context,
    String categoryId,
    String label,
    IconData icon,
    Color color,
    bool isDark,
  ) {
    return DragTarget<Map<String, dynamic>>(
      onWillAccept: (data) => data != null,
      onAccept: (repair) {
        onDrop(repair, categoryId);
      },
      builder: (context, candidateData, rejectedData) {
        final isHovering = candidateData.isNotEmpty;
        
        return AnimatedContainer(
          duration: const Duration(milliseconds: 200),
          decoration: BoxDecoration(
            color: isHovering 
                ? color.withOpacity(0.3)
                : (isDark ? Colors.grey[800] : Colors.white),
            borderRadius: BorderRadius.circular(20),
            border: Border.all(
              color: isHovering ? color : color.withOpacity(0.5),
              width: isHovering ? 4 : 2,
            ),
            boxShadow: [
              if (isHovering)
                BoxShadow(
                  color: color.withOpacity(0.5),
                  blurRadius: 20,
                  spreadRadius: 2,
                ),
            ],
          ),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                icon,
                size: isHovering ? 64 : 56,
                color: isHovering ? color : color.withOpacity(0.7),
              ),
              const SizedBox(height: 16),
              Text(
                label,
                style: TextStyle(
                  fontSize: isHovering ? 24 : 20,
                  fontWeight: FontWeight.bold,
                  color: isHovering ? color : null,
                ),
                textAlign: TextAlign.center,
              ),
              if (isHovering) ...[
                const SizedBox(height: 8),
                Text(
                  'Relâchez ici',
                  style: TextStyle(
                    fontSize: 14,
                    color: color,
                  ),
                ),
              ],
            ],
          ),
        );
      },
    );
  }
}
