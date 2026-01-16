import 'package:flutter/material.dart';
import 'package:geekboard_desktop/theme/macos_theme.dart';

class PriceCheckDialog extends StatefulWidget {
  final double currentPrice;
  final Function(double) onPriceUpdate;

  const PriceCheckDialog({
    Key? key,
    required this.currentPrice,
    required this.onPriceUpdate,
  }) : super(key: key);

  @override
  State<PriceCheckDialog> createState() => _PriceCheckDialogState();
}

class _PriceCheckDialogState extends State<PriceCheckDialog> {
  final TextEditingController _priceController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _priceController.text = widget.currentPrice.toStringAsFixed(2);
  }

  @override
  Widget build(BuildContext context) {
    return Dialog(
      backgroundColor: Colors.transparent,
      insetPadding: const EdgeInsets.all(16),
      child: Container(
        width: 400,
        decoration: BoxDecoration(
          color: const Color(0xFF1C1C1E), // Apple Dark Gray
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.5),
              blurRadius: 20,
              offset: const Offset(0, 10),
            )
          ],
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Header Orange
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
              decoration: const BoxDecoration(
                color: MacOSTheme.warningOrange,
                borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.warning_amber_rounded, color: Colors.white, size: 24),
                  const SizedBox(width: 12),
                  const Text(
                    "Vérification du prix",
                    style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                  const Spacer(),
                  Container(
                    width: 32, height: 32,
                    decoration: BoxDecoration(color: Colors.black.withOpacity(0.1), borderRadius: BorderRadius.circular(8)),
                  ),
                ],
              ),
            ),
            
            Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                children: [
                  // Icone Jaune
                  const Icon(Icons.euro_symbol_rounded, size: 48, color: MacOSTheme.warningOrange),
                  const SizedBox(height: 16),
                  
                  const Text("Attention : Prix à 0€", style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Colors.white)),
                  const SizedBox(height: 8),
                  Text(
                    "Le prix de cette réparation est actuellement de 0€. Voulez-vous le mettre à jour avant de terminer ?",
                    textAlign: TextAlign.center,
                    style: TextStyle(color: Colors.grey[400], height: 1.4),
                  ),
                  const SizedBox(height: 24),
                  
                  // Input Prix
                  Align(
                    alignment: Alignment.centerLeft,
                    child: Text("Nouveau prix (€)", style: TextStyle(color: Colors.grey[400], fontSize: 12)),
                  ),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      Expanded(
                        child: Container(
                          height: 48,
                          decoration: BoxDecoration(
                            color: const Color(0xFF2C2C2E),
                            borderRadius: const BorderRadius.horizontal(left: Radius.circular(8)),
                            border: Border.all(color: Colors.grey[800]!),
                          ),
                          alignment: Alignment.center,
                          child: TextField(
                            controller: _priceController,
                            keyboardType: const TextInputType.numberWithOptions(decimal: true),
                            textAlign: TextAlign.center,
                            style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold),
                            decoration: const InputDecoration(
                              border: InputBorder.none,
                              contentPadding: EdgeInsets.zero,
                            ),
                          ),
                        ),
                      ),
                      Container(
                        height: 48,
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        decoration: BoxDecoration(
                          color: const Color(0xFF3A3A3C),
                          borderRadius: const BorderRadius.horizontal(right: Radius.circular(8)),
                          border: Border.all(color: Colors.grey[800]!),
                        ),
                        alignment: Alignment.center,
                        child: const Text("€", style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            
            // Actions
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                border: Border(top: BorderSide(color: Colors.white.withOpacity(0.1))),
              ),
              child: Row(
                children: [
                  Expanded(
                    child: OutlinedButton(
                      style: OutlinedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        side: const BorderSide(color: Colors.white24),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                        foregroundColor: Colors.white,
                      ),
                      onPressed: () => Navigator.pop(context, true), // Confirmer 0€
                      child: const Text("Confirmer 0€"),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    flex: 2,
                    child: ElevatedButton.icon(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF2C2C2E),
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                        elevation: 0,
                      ),
                      icon: const Icon(Icons.save_rounded, size: 18, color: Colors.white),
                      label: const Text("Mettre à jour et terminer", style: TextStyle(color: Colors.white)),
                      onPressed: () {
                         final newPrice = double.tryParse(_priceController.text.replaceAll(',', '.')) ?? 0.0;
                         widget.onPriceUpdate(newPrice);
                         Navigator.pop(context, true);
                      },
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class CompletionOptionsDialog extends StatelessWidget {
  final int repairId;
  
  const CompletionOptionsDialog({Key? key, required this.repairId}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Dialog(
      backgroundColor: Colors.transparent,
      insetPadding: const EdgeInsets.all(16),
      child: Container(
        width: 450,
        decoration: BoxDecoration(
          color: const Color(0xFF0D0D0D), // Deep Dark
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: Colors.white.withOpacity(0.1)),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Header Blue
            Container(
              padding: const EdgeInsets.all(20),
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  colors: [Color(0xFF007AFF), Color(0xFF5AC8FA)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
              ),
              child: Column(
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Row(
                        children: [
                          Icon(Icons.build_circle_outlined, color: Colors.white),
                          SizedBox(width: 8),
                          Text(
                            "Terminer la réparation en cours",
                            style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16),
                          ),
                        ],
                      ),
                      Container(
                        width: 32, height: 32,
                        decoration: BoxDecoration(color: Colors.white.withOpacity(0.2), borderRadius: BorderRadius.circular(16)),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    decoration: BoxDecoration(
                      color: Colors.white.withOpacity(0.2),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      "Réparation #$repairId en cours",
                      style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
                    ),
                  ),
                ],
              ),
            ),
            
            Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                children: [
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 16),
                    decoration: BoxDecoration(
                      color: const Color(0xFF0055CC), // Blue Info
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Row(
                      children: [
                        Icon(Icons.help_outline, color: Colors.white, size: 20),
                        SizedBox(width: 12),
                        Text("Comment terminer cette réparation ?", style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                  
                  _buildOption(
                    context, 
                    title: "Réparation terminée", 
                    subtitle: "L'appareil fonctionne parfaitement",
                    icon: Icons.check_circle_rounded,
                    color: MacOSTheme.successGreen,
                    value: 'reparation_effectue',
                  ),
                  
                  _buildOption(
                    context, 
                    title: "Envoyer un devis", 
                    subtitle: "Pièces supplémentaires nécessaires",
                    icon: Icons.receipt_long_rounded,
                    color: Colors.white,
                    value: 'devis_envoye', // A verifier
                  ),
                  
                  _buildOption(
                    context, 
                    title: "Commander des pièces", 
                    subtitle: "Passer une commande fournisseur",
                    icon: Icons.shopping_cart_rounded,
                    color: Colors.white,
                    value: 'en_attente_piece',
                  ),
                  
                  const SizedBox(height: 8),
                  OutlinedButton.icon(
                      style: OutlinedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        backgroundColor: const Color(0xFF1C1C1E),
                        side: BorderSide(color: Colors.grey[800]!),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        minimumSize: const Size(double.infinity, 50),
                      ),
                      icon: const Icon(Icons.more_horiz, color: Colors.white),
                      label: const Text("Plus d'options", style: TextStyle(color: Colors.white)),
                      onPressed: () {
                          // TODO: Show more options
                          Navigator.pop(context, 'reparation_effectue'); // Default for now
                      },
                  ),
                ],
              ),
            ),
            
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
              alignment: Alignment.centerRight,
              child: TextButton.icon(
                icon: const Icon(Icons.close, color: Colors.white70),
                label: const Text("Fermer", style: TextStyle(color: Colors.white70)),
                onPressed: () => Navigator.pop(context, null),
              ),
            )
          ],
        ),
      ),
    );
  }

  Widget _buildOption(BuildContext context, {
    required String title, 
    required String subtitle, 
    required IconData icon, 
    required Color color,
    required String value,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Material(
        color: const Color(0xFF2C2C2E),
        borderRadius: BorderRadius.circular(12),
        child: InkWell(
          onTap: () => Navigator.pop(context, value),
          borderRadius: BorderRadius.circular(12),
          child: Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              border: Border.all(color: Colors.white.withOpacity(0.05)),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.1),
                    shape: BoxShape.circle,
                  ),
                  child: Icon(icon, color: color, size: 24),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(title, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16)),
                      const SizedBox(height: 4),
                      Text(subtitle, style: TextStyle(color: Colors.grey[400], fontSize: 13)),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
