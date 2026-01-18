import 'package:flutter/material.dart';
import '../../../../config/api_config.dart';
import 'package:url_launcher/url_launcher.dart';

class ExtensionTab extends StatelessWidget {
  const ExtensionTab({super.key});

  Future<void> _launchDownload() async {
    final url = Uri.parse('${ApiConfig.siteUrl}/assets/downloads/download_servo.php');
    if (!await launchUrl(url)) {
      // Handle error
    }
  }

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildHeroCard(context),
          const SizedBox(height: 24),
          _buildWarning(context),
          const SizedBox(height: 24),
          _buildGuideCard(context),
        ],
      ),
    );
  }

  Widget _buildHeroCard(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [Colors.blue[50]!, Colors.blue[100]!],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(12),
        boxShadow: const [BoxShadow(color: Colors.black12, blurRadius: 4, offset: Offset(0, 2))],
      ),
      padding: const EdgeInsets.all(30),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(15),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(12),
              boxShadow: const [BoxShadow(color: Colors.black12, blurRadius: 4)],
            ),
            child: const Icon(Icons.extension, size: 40, color: Colors.blue),
          ),
          const SizedBox(width: 20),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  "SERVO - Assistant d'Achat Réparation",
                  style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Color(0xFF0c4a6e)),
                ),
                const SizedBox(height: 10),
                const Text(
                  "Simplifiez vos commandes ! Importez vos pièces détachées depuis Utopya et Mobilax directement dans SERVO. Ajoutez des produits et créez des clients sans changer d'onglet.",
                  style: TextStyle(color: Color(0xFF0369a1), height: 1.5, fontSize: 15),
                ),
                const SizedBox(height: 20),
                ElevatedButton.icon(
                  onPressed: _launchDownload,
                  icon: const Icon(Icons.download),
                  label: const Text("Télécharger l'extension (v1.0.0)"),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Theme.of(context).primaryColor,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildWarning(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.orange[50],
        border: Border.all(color: Colors.orange[200]!),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        children: [
          Icon(Icons.warning_amber_rounded, color: Colors.orange[800]),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text("Mode Développeur requis", style: TextStyle(fontWeight: FontWeight.bold, color: Colors.orange[900])),
                const SizedBox(height: 4),
                Text("En attendant la validation sur le Chrome Web Store, vous devez installer l'extension manuellement.", 
                  style: TextStyle(color: Colors.orange[900])
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildGuideCard(BuildContext context) {
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: BorderSide(color: Theme.of(context).dividerColor),
      ),
      child: Column(
        children: [
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: Theme.of(context).hoverColor,
              border: Border(bottom: BorderSide(color: Theme.of(context).dividerColor)),
            ),
            child: Row(
              children: [
                Icon(Icons.build_circle, color: Theme.of(context).iconTheme.color),
                const SizedBox(width: 12),
                const Text("Guide d'installation", style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              children: [
                _buildStep(1, "Téléchargez le fichier ci-dessus. Renommez-le en servo.zip"),
                const SizedBox(height: 16),
                _buildStep(2, "Extrayez le fichier : double-cliquez sur servo.zip. Vous obtiendrez un dossier servo-extension."),
                const SizedBox(height: 16),
                _buildStep(3, "Allez a l'url suivante : chrome://extensions et activez le \"Mode développeur\" (en haut à droite)."),
                const SizedBox(height: 16),
                _buildStep(4, "Cliquez sur \"Charger l'extension non empaquetée\" puis sélectionnez le dossier servo-extension."),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStep(int number, String text) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        CircleAvatar(
          radius: 12,
          backgroundColor: Colors.grey[200],
          child: Text(number.toString(), style: const TextStyle(fontSize: 12, color: Colors.black87, fontWeight: FontWeight.bold)),
        ),
        const SizedBox(width: 16),
        Expanded(child: Text(text, style: const TextStyle(height: 1.4))),
      ],
    );
  }
}
