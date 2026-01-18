import 'package:flutter/material.dart';
import 'label_preview_dialog.dart';

class LabelsTab extends StatefulWidget {
  const LabelsTab({super.key});

  @override
  State<LabelsTab> createState() => _LabelsTabState();
}

class _LabelsTabState extends State<LabelsTab> {
  String _selectedLayout = '4x6_moderne';

  final List<Map<String, String>> _layouts = [
    {'id': '4x6_moderne', 'name': '4x6" Moderne', 'format': '4x6"', 'type': 'Thermique'},
    {'id': '4x6_business', 'name': '4x6" Business', 'format': '4x6"', 'type': 'Thermique'},
    {'id': 'a4_moderne', 'name': 'A4 Moderne', 'format': 'A4', 'type': 'Couleur'},
    {'id': 'a4_split', 'name': 'A4 Split (Client/Atelier)', 'format': 'A4', 'type': 'Spécial'},
    {'id': 'mini_qr', 'name': 'Mini QR Only', 'format': '2x2"', 'type': 'Thermique'},
  ];

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      padding: const EdgeInsets.only(bottom: 24),
      child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          "Choisissez le format d'étiquette pour vos impressions.",
          style: TextStyle(color: Colors.grey, fontSize: 14),
        ),
        const SizedBox(height: 24),

        GridView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 3,
              crossAxisSpacing: 16,
              mainAxisSpacing: 16,
              childAspectRatio: 1.5,
            ),
            itemCount: _layouts.length,
            itemBuilder: (context, index) {
              final layout = _layouts[index];
              final isSelected = _selectedLayout == layout['id'];

              return InkWell(
                onTap: () => setState(() => _selectedLayout = layout['id']!),
                child: Container(
                  decoration: BoxDecoration(
                    color: Theme.of(context).cardColor,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(
                      color: isSelected ? Theme.of(context).primaryColor : Colors.transparent,
                      width: 2,
                    ),
                  ),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(
                        Icons.print, 
                        size: 32, 
                        color: isSelected ? Theme.of(context).primaryColor : Theme.of(context).iconTheme.color
                      ),
                      const SizedBox(height: 12),
                      Text(
                        layout['name']!,
                        style: TextStyle(
                          color: isSelected ? Theme.of(context).primaryColor : Theme.of(context).textTheme.bodyLarge?.color,
                          fontWeight: FontWeight.bold,
                        ),
                        textAlign: TextAlign.center,
                      ),
                      const SizedBox(height: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: Colors.black26,
                          borderRadius: BorderRadius.circular(4),
                        ),
                        child: Text(
                          "${layout['type']} • ${layout['format']}",
                          style: const TextStyle(color: Colors.grey, fontSize: 12),
                        ),
                      ),
                    ],
                  ),
                ),
              );
            },
          ),


        const SizedBox(height: 20),
        Row(
          children: [
            Expanded(
              child: OutlinedButton.icon(
                onPressed: _showPreview,
                icon: const Icon(Icons.visibility),
                label: const Text("Prévisualiser"),
                style: OutlinedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(vertical: 16),
                ),
              ),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: ElevatedButton.icon(
                onPressed: () {
                  ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Layout $_selectedLayout sauvegardé")));
                },
                icon: const Icon(Icons.save),
                label: const Text("Enregistrer le layout"),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.blue,
                  padding: const EdgeInsets.symmetric(vertical: 16),
                ),
              ),
            ),
          ],
        ),
      ],
      ),
    );
  }

  void _showPreview() {
    final layout = _layouts.firstWhere((l) => l['id'] == _selectedLayout);
    showDialog(
      context: context,
      builder: (context) => LabelPreviewDialog(
        layoutId: _selectedLayout,
        layoutName: layout['name']!,
      ),
    );
  }
}
