import 'dart:typed_data';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:printing/printing.dart';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:qr_flutter/qr_flutter.dart';
import '../../services/auth_service.dart';
import '../../theme/macos_theme.dart';
import '../../services/api_service.dart';
import '../../config/api_config.dart';
import 'stop_repair_dialogs.dart';
import '../../widgets/sms_template_selection_modal.dart';
import '../../widgets/sms_preview_modal.dart';
import '../create_command_dialog.dart';

/// Modal de détail de réparation - Style MacOS Taohe
class RepairDetailModal extends StatefulWidget {
  final Map<String, dynamic> repair;
  final ApiService apiService;
  final VoidCallback? onUpdate;

  const RepairDetailModal({
    super.key,
    required this.repair,
    required this.apiService,
    this.onUpdate,
  });

  @override
  State<RepairDetailModal> createState() => _RepairDetailModalState();
}


class _RepairDetailModalState extends State<RepairDetailModal> {
  bool _isLoading = false;
  Map<String, dynamic> _repairData = {};

  @override
  void initState() {
    super.initState();
    _repairData = Map.from(widget.repair);
    _fetchFullDetails();
  }

  Future<void> _fetchFullDetails() async {
    try {
      final response = await widget.apiService.get('/reparations/get.php?id=${widget.repair['id']}');
      if (response['success'] == true && response['reparation'] != null) {
        if (mounted) {
          setState(() {
            final newData = response['reparation'] as Map<String, dynamic>;
            _repairData.addAll(newData);
            
            // Mapper les champs qui pourraient avoir des noms différents ou être nuls
            if (newData['prix_reparation'] != null) _repairData['prix_reparation'] = newData['prix_reparation'];
            if (newData['description_probleme'] != null) _repairData['description_probleme'] = newData['description_probleme'];
            if (newData['client_telephone'] != null) _repairData['client_telephone'] = newData['client_telephone'];
            if (newData['note_interne'] != null) _repairData['note_interne'] = newData['note_interne'];
            if (newData['photo_appareil'] != null) _repairData['photo_appareil'] = newData['photo_appareil'];
            
            // Autres champs potentiels
            if (newData['date_reception'] != null) _repairData['date_reception'] = newData['date_reception'];
          });
        }
      }
    } catch (e) {
      print("Erreur chargement détails: $e");
    }
  }

  Future<void> _startRepair() async {
    final authService = context.read<AuthService>();
    
    // 1. Check active repair
    setState(() => _isLoading = true);
    try {
        final response = await widget.apiService.post(ApiConfig.repairAssignmentEndpoint, {
            'action': 'check_active_repair'
        });
        
        if (response['success'] == true && response['has_active_repair'] == true) {
             final active = response['active_repair'];
             if (active != null) {
                 if (mounted) {
                     setState(() => _isLoading = false);
                     
                     // Afficher une modale interactive
                     bool goToActive = await showDialog(
                         context: context,
                         builder: (ctx) => AlertDialog(
                             backgroundColor: const Color(0xFF1C1C1E),
                             title: Row(
                               children: [
                                 const Icon(Icons.warning_amber_rounded, color: MacOSTheme.warningOrange),
                                 const SizedBox(width: 10),
                                 Expanded(child: const Text("Réparation déjà en cours", style: TextStyle(color: Colors.white))),
                               ],
                             ),
                             content: Column(
                               mainAxisSize: MainAxisSize.min,
                               crossAxisAlignment: CrossAxisAlignment.start,
                               children: [
                                 Text(
                                   "Vous travaillez déjà sur la réparation #${active['id']} (${active['modele'] ?? 'Appareil inconnu'}).",
                                   style: const TextStyle(color: Colors.white70),
                                 ),
                                 const SizedBox(height: 16),
                                 Container(
                                   padding: const EdgeInsets.all(12),
                                   decoration: BoxDecoration(
                                     color: Colors.black26, 
                                     borderRadius: BorderRadius.circular(8),
                                     border: Border.all(color: Colors.white10)
                                   ),
                                   child: Row(
                                     children: [
                                        const Icon(Icons.build, color: MacOSTheme.accentBlue, size: 20),
                                        const SizedBox(width: 12),
                                        Expanded(
                                          child: Text(
                                            "${active['modele'] ?? ''} - ${active['client_nom'] ?? ''}",
                                            style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
                                          ),
                                        )
                                     ],
                                   ),
                                 ),
                                 const SizedBox(height: 16),
                                 const Text("Voulez-vous ouvrir ce dossier pour le terminer ?", style: TextStyle(color: Colors.white70)),
                               ],
                             ),
                             actions: [
                                 TextButton(
                                     onPressed: () => Navigator.pop(ctx, false),
                                     child: const Text("Annuler"),
                                 ),
                                 ElevatedButton.icon(
                                     style: ElevatedButton.styleFrom(backgroundColor: MacOSTheme.accentBlue),
                                     icon: const Icon(Icons.open_in_new, size: 16, color: Colors.white),
                                     onPressed: () => Navigator.pop(ctx, true),
                                     label: const Text("Voir le dossier en cours", style: TextStyle(color: Colors.white)),
                                 )
                             ],
                         )
                     ) ?? false;
                     
                     if (goToActive) {
                         Navigator.pop(context); // Fermer la modale actuelle
                         
                         // Ouvrir la modale de la réparation active
                         // On doit s'assurer que les données sont compatibles. 
                         // active contient déjà beaucoup d'infos via getUserActiveRepair en PHP
                         showDialog(
                            context: context,
                            builder: (_) => RepairDetailModal(
                                repair: active, 
                                apiService: widget.apiService,
                                onUpdate: widget.onUpdate,
                            )
                         );
                     }
                 }
                 return;
             }
        }
        
        // 2. Assign repair
         if (!mounted) return;
        setState(() => _isLoading = false); // Stop loading to show dialog
        
        bool confirm = await showDialog(
            context: context,
            builder: (ctx) => AlertDialog(
                title: const Text("Démarrer la réparation"),
                content: Text("Voulez-vous commencer la réparation #${widget.repair['id']} ?"),
                actions: [
                    TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text("Annuler")),
                    ElevatedButton(
                      style: ElevatedButton.styleFrom(backgroundColor: MacOSTheme.successGreen),
                      onPressed: () => Navigator.pop(ctx, true), 
                      child: const Text("Démarrer")
                    ),
                ]
            )
        ) ?? false;
        
        if (!confirm) return;

        setState(() => _isLoading = true);
        final authService = context.read<AuthService>();
        final assignResponse = await widget.apiService.post(ApiConfig.repairAssignmentEndpoint, {
            'action': 'assign_repair',
            'reparation_id': widget.repair['id'],
            'shop_id': authService.currentShop?.id
        });
        
        if (assignResponse['success'] == true) {
            if (mounted) {
                 ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Réparation démarrée avec succès'), backgroundColor: MacOSTheme.successGreen),
                 );
                 widget.onUpdate?.call();
                 Navigator.pop(context);
            }
        } else {
             // Vérifier si c'est un conflit avec une réparation active
             final activeRepair = assignResponse['active_repair'];
             if (activeRepair != null && mounted) {
                 setState(() => _isLoading = false);
                 _showActiveRepairConflictDialog(activeRepair);
             } else {
                 throw Exception(assignResponse['message'] ?? "Erreur lors de l'attribution");
             }
        }

    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }
  
  void _showActiveRepairConflictDialog(Map<String, dynamic> active) async {
      bool goToActive = await showDialog(
          context: context,
          builder: (ctx) => AlertDialog(
              backgroundColor: const Color(0xFF1C1C1E),
              title: Row(
                children: [
                  const Icon(Icons.warning_amber_rounded, color: MacOSTheme.warningOrange),
                  const SizedBox(width: 10),
                  const Expanded(child: Text("Réparation déjà en cours", style: TextStyle(color: Colors.white))),
                ],
              ),
              content: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    "Vous travaillez déjà sur la réparation #${active['id']} (${active['modele'] ?? 'Appareil inconnu'}).",
                    style: const TextStyle(color: Colors.white70),
                  ),
                  const SizedBox(height: 16),
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.black26, 
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: Colors.white10)
                    ),
                    child: Row(
                      children: [
                         const Icon(Icons.build, color: MacOSTheme.accentBlue, size: 20),
                         const SizedBox(width: 12),
                         Expanded(
                           child: Text(
                             "${active['modele'] ?? ''} - ${active['client_nom'] ?? ''}",
                             style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
                           ),
                         )
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                  const Text("Voulez-vous ouvrir ce dossier pour le terminer ?", style: TextStyle(color: Colors.white70)),
                ],
              ),
              actions: [
                  TextButton(
                      onPressed: () => Navigator.pop(ctx, false),
                      child: const Text("Annuler"),
                  ),
                  ElevatedButton.icon(
                      style: ElevatedButton.styleFrom(backgroundColor: MacOSTheme.accentBlue),
                      icon: const Icon(Icons.open_in_new, size: 16, color: Colors.white),
                      onPressed: () => Navigator.pop(ctx, true),
                      label: const Text("Voir le dossier en cours", style: TextStyle(color: Colors.white)),
                  )
              ],
          )
      ) ?? false;
      
      if (goToActive && mounted) {
          Navigator.pop(context); // Fermer la modale actuelle
          
          // Ouvrir la modale de la réparation active
          showDialog(
             context: context,
             builder: (_) => RepairDetailModal(
                 repair: active, 
                 apiService: widget.apiService,
                 onUpdate: widget.onUpdate,
             )
          );
      }
  }

  Future<void> _stopRepair() async {
        // 1. Déterminer le prix actuel (string ou double)
        double currentPrice = 0.0;
        try {
           currentPrice = double.tryParse(widget.repair['prix_reparation']?.toString() ?? '0') ?? 0.0;
        } catch (_) {}

        // 2. Si prix = 0, demander confirmation ou mise à jour
        if (currentPrice == 0) {
             final result = await showDialog(
                 context: context,
                 builder: (ctx) => PriceCheckDialog(
                     currentPrice: currentPrice,
                     onPriceUpdate: (newPrice) {
                         // TODO: Appeler API pour mettre à jour le prix
                         setState(() {
                             widget.repair['prix_reparation'] = newPrice;
                         });
                         _updatePrice(newPrice);
                     }
                 )
             );

             // Si null, l'utilisateur a annulé (clic extérieur ou bouton annuler non implémenté dans la dialog pour l'instant)
             // Mais notre dialog "Confirmer 0" renvoie true. "Mettre à jour" renvoie true aussi.
             // Si on annule, le result est null.
             if (result == null) return;
        } else {
            // Confirmation simple pour arrêt normal si prix > 0 ?
            // Le workflow demandé semble dire : "arrêt -> verif prix -> choix statut"
            bool confirm = await showDialog(
              context: context,
              builder: (ctx) => AlertDialog(
                  title: const Text("Arrêter la réparation"),
                  content: const Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                       Icon(Icons.help_outline_rounded, color: MacOSTheme.warningOrange, size: 48),
                       SizedBox(height: 16),
                       Text(
                         "Êtes-vous sûr de vouloir arrêter cette réparation ?",
                         style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                         textAlign: TextAlign.center,
                       ),
                       SizedBox(height: 8),
                       Text("Vous pourrez choisir le statut après confirmation.", textAlign: TextAlign.center, style: TextStyle(color: Colors.white70)),
                    ],
                  ),
                  actions: [
                      TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text("Annuler")),
                      ElevatedButton(
                        style: ElevatedButton.styleFrom(backgroundColor: MacOSTheme.warningOrange),
                        onPressed: () => Navigator.pop(ctx, true), 
                        child: const Text("Oui, arrêter", style: TextStyle(color: Colors.black))
                      ),
                  ]
              )
          ) ?? false;
          if (!confirm) return;
        }

        // 3. Choisir le statut de fin
        final String? selectedStatus = await showDialog(
             context: context,
             builder: (ctx) => CompletionOptionsDialog(repairId: widget.repair['id'])
        );

        if (selectedStatus == null) return; // Annulation du choix

        // 4. Exécuter l'arrêt avec le statut choisi
        setState(() => _isLoading = true);

        try {
            final authService = context.read<AuthService>();
            final response = await widget.apiService.post(ApiConfig.repairAssignmentEndpoint, {
                'action': 'complete_active_repair',
                'reparation_id': widget.repair['id'],
                'shop_id': authService.currentShop?.id,
                'final_status': selectedStatus // Utilisation du statut choisi (ex: reparation_effectue)
            });

            if (response['success'] == true) {
                 if (mounted) {
                     ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Intervention terminée'), backgroundColor: MacOSTheme.successGreen),
                     );
                     widget.onUpdate?.call();
                     Navigator.pop(context);
                 }
            } else {
                 throw Exception(response['message'] ?? "Erreur lors de la fin d'intervention");
            }
        } catch (e) {
             if (mounted) {
                 ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(content: Text('Erreur: $e'), backgroundColor: MacOSTheme.dangerRed),
                 );
             }
        } finally {
             if (mounted) setState(() => _isLoading = false);
        }
  }

  Future<void> _updatePrice(double newPrice) async {
      try {
          final authService = context.read<AuthService>();
          final response = await widget.apiService.post(ApiConfig.reparationsUpdatePriceEndpoint, {
              'reparation_id': widget.repair['id'],
              'price': newPrice,
              'shop_id': authService.currentShop?.id
          });
          
          if (response['success'] == true) {
               // Succès silencieux ou toast léger
               print("Prix mis à jour : $newPrice");
          } else {
               print("Erreur maj prix: ${response['message']}");
          }
      } catch (e) {
          print("Erreur maj prix (exception): $e");
      }
  }

  Future<void> _sendSms() async {
    final phone = widget.repair['client_telephone'];
    // Allow trying even if phone is missing, preview allows editing
    
    // 1. Select Template
    showDialog(
      context: context,
      builder: (ctx) => SmsTemplateSelectionModal(
        apiService: widget.apiService,
        repairId: widget.repair['id'].toString(),
        onTemplateSelected: (template) async {
             // 2. Open Preview & Send
             final sent = await showDialog(
                context: context,
                builder: (ctx) => SmsPreviewModal(
                    apiService: widget.apiService,
                    repairId: widget.repair['id'].toString(),
                    templateId: template['id'].toString(),
                    templateName: template['titre'],
                )
             );
             
             if (sent == true) {
                 // Refresh logs or history if needed
                 widget.onUpdate?.call();
             }
        },
      ),
    );
  }

  void _showDevisDialog() {
    showDialog(
      context: context,
      builder: (ctx) => _DevisDialog(repair: widget.repair, apiService: widget.apiService),
    );
  }

  void _showStatusDialog() {
    showDialog(
      context: context,
      builder: (ctx) => _StatusDialog(
        repair: widget.repair,
        apiService: widget.apiService,
        onUpdate: widget.onUpdate,
      ),
    );
  }

  void _showPriceDialog() {
    showDialog(
      context: context,
      builder: (ctx) => _PriceDialog(
        repair: widget.repair,
        apiService: widget.apiService,
        onUpdate: widget.onUpdate,
      ),
    );
  }

  void _showOrderDialog() async {
    final client = {
      'id': widget.repair['client_id'],
      'nom': widget.repair['client_nom'],
      'prenom': widget.repair['client_prenom'],
      'telephone': widget.repair['client_telephone'],
      'email': widget.repair['client_email'],
    };
    
    final pieceName = "${widget.repair['marque'] ?? ''} ${widget.repair['modele'] ?? ''}".trim();

    final result = await showDialog(
      context: context,
      builder: (ctx) => CreateCommandDialog(
        apiService: widget.apiService,
        initialClient: client,
        initialPieceName: pieceName,
      ),
    );
    
    if (result == true) {
      if (mounted) {
         ScaffoldMessenger.of(context).showSnackBar(
           const SnackBar(content: Text("Commande créée avec succès"), backgroundColor: MacOSTheme.successGreen)
         );
         // Optionnel : Mettre à jour le statut de la réparation si nécessaire ou juste logs
         widget.onUpdate?.call();
      }
    }
  }

  void _showHistoryDialog() {
    showDialog(
      context: context,
      builder: (ctx) => _HistoryDialog(repair: widget.repair, apiService: widget.apiService),
    );
  }

  void _showNoteDialog() {
    showDialog(
      context: context,
      builder: (ctx) => _NoteDialog(
        repair: widget.repair,
        apiService: widget.apiService,
        onUpdate: widget.onUpdate,
      ),
    );
  }

  void _printLabel() {
    showDialog(
      context: context,
      builder: (ctx) => _LabelPreviewDialog(
        repair: widget.repair,
        apiService: widget.apiService,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final r = _repairData;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Container(
        width: 700,
        constraints: const BoxConstraints(maxHeight: 800),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Header with gradient
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFF007AFF), Color(0xFF5AC8FA)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: const BorderRadius.only(
                  topLeft: Radius.circular(20),
                  topRight: Radius.circular(20),
                ),
              ),
              child: Row(
                children: [
                  IconButton(
                    icon: const Icon(Icons.arrow_back, color: Colors.white),
                    onPressed: () => Navigator.pop(context),
                    tooltip: 'Retour',
                  ),
                  const SizedBox(width: 16),
                  Container(
                    width: 12,
                    height: 12,
                    decoration: BoxDecoration(
                      color: MacOSTheme.successGreen,
                      shape: BoxShape.circle,
                      boxShadow: [BoxShadow(color: MacOSTheme.successGreen.withOpacity(0.5), blurRadius: 10)],
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Réparation #${r['id']}',
                          style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Colors.white),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          '${r['marque'] ?? ''} ${r['modele'] ?? ''} - ${_getStatusLabel(r['statut'])}',
                          style: TextStyle(color: Colors.white.withOpacity(0.8), fontSize: 14),
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    onPressed: () => Navigator.pop(context),
                    icon: const Icon(Icons.close, color: Colors.white),
                  ),
                ],
              ),
            ),
            
            // Body
            Flexible(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // ⭐ Actions Rapides
                    _buildSection(
                      icon: Icons.flash_on,
                      title: 'Action rapide',
                      child: Wrap(
                        spacing: 10,
                        runSpacing: 10,
                        children: [
                          _ActionButton(
                            icon: Icons.receipt_long,
                            label: 'DEVIS',
                            color: const Color(0xFF7c3aed),
                            bgColor: isDark ? const Color(0xFF7c3aed).withOpacity(0.15) : const Color(0xFFddd6fe),
                            onTap: _showDevisDialog,
                          ),
                          _ActionButton(
                            icon: Icons.swap_horiz,
                            label: 'STATUT',
                            color: const Color(0xFF16a34a),
                            bgColor: isDark ? const Color(0xFF16a34a).withOpacity(0.15) : const Color(0xFFdcfce7),
                            onTap: _showStatusDialog,
                          ),
                          _ActionButton(
                            icon: Icons.euro,
                            label: 'PRIX',
                            color: const Color(0xFFd97706),
                            bgColor: isDark ? const Color(0xFFd97706).withOpacity(0.15) : const Color(0xFFfef3c7),
                            onTap: _showPriceDialog,
                          ),
                          _ActionButton(
                            icon: Icons.shopping_cart,
                            label: 'COMMANDER',
                            color: const Color(0xFF0891b2),
                            bgColor: isDark ? const Color(0xFF0891b2).withOpacity(0.15) : const Color(0xFFcffafe),
                            onTap: _showOrderDialog,
                          ),
                          _ActionButton(
                            icon: Icons.print,
                            label: 'ÉTIQUETTE',
                            color: isDark ? Colors.grey.shade400 : const Color(0xFF6b7280),
                            bgColor: isDark ? Colors.white.withOpacity(0.1) : const Color(0xFFf3f4f6),
                            onTap: _printLabel,
                          ),
                          _ActionButton(
                            icon: Icons.history,
                            label: 'HISTORIQUE',
                            color: const Color(0xFFea580c),
                            bgColor: isDark ? const Color(0xFFea580c).withOpacity(0.15) : const Color(0xFFffedd5),
                            onTap: _showHistoryDialog,
                          ),
                        ],
                      ),
                    ),
                    
                    const SizedBox(height: 16),
                    
                    // Bouton Démarrer/Arrêter Dynamique
                    Builder(
                      builder: (context) {
                          final authService = context.watch<AuthService>();
                          final currentUser = authService.currentUser;
                          final r = widget.repair;
                          final isMyActiveRepair = r['employe_id'].toString() == currentUser?.id.toString() && 
                                                 (r['statut'] == 'en_cours_intervention' || r['statut'] == 'en_cours_diagnostique');
                          final isFinished = ['termine', 'restitue', 'abandonne', 'annule', 'archive'].contains(r['statut']);

                          if (isMyActiveRepair) {
                              return SizedBox(
                                width: double.infinity,
                                child: ElevatedButton.icon(
                                  onPressed: _isLoading ? null : _stopRepair,
                                  icon: _isLoading
                                      ? SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: isDark ? MacOSTheme.dangerRed : Colors.white))
                                      : const Icon(Icons.stop_circle_outlined, size: 24),
                                  label: const Text('ARRÊTER LA RÉPARATION', style: TextStyle(fontWeight: FontWeight.bold, letterSpacing: 0.5)),
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: isDark ? MacOSTheme.dangerRed.withOpacity(0.15) : MacOSTheme.dangerRed,
                                    foregroundColor: isDark ? MacOSTheme.dangerRed : Colors.white,
                                    padding: const EdgeInsets.symmetric(vertical: 18),
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                                  ),
                                ),
                              );
                          } else if (!isFinished) {
                              return SizedBox(
                                width: double.infinity,
                                child: ElevatedButton.icon(
                                  onPressed: _isLoading ? null : _startRepair,
                                  icon: _isLoading
                                      ? SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: isDark ? MacOSTheme.successGreen : Colors.white))
                                      : const Icon(Icons.play_circle_filled, size: 24),
                                  label: const Text('DÉMARRER LA RÉPARATION', style: TextStyle(fontWeight: FontWeight.bold, letterSpacing: 0.5)),
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: isDark ? MacOSTheme.successGreen.withOpacity(0.15) : MacOSTheme.successGreen,
                                    foregroundColor: isDark ? MacOSTheme.successGreen : Colors.white,
                                    padding: const EdgeInsets.symmetric(vertical: 18),
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                                  ),
                                ),
                              );
                          }
                          return const SizedBox.shrink();
                      }
                    ),
                    
                    const SizedBox(height: 20),
                    
                    // Informations
                    _buildSection(
                      icon: Icons.info_outline,
                      title: 'Inforamtions',
                      child: _buildInfoGrid(r),
                    ),
                    
                    const SizedBox(height: 16),
                    
                    // Description du problème
                    _buildSection(
                      icon: Icons.warning_amber,
                      title: 'Description du probleme',
                      child: Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: isDark ? Colors.white.withOpacity(0.05) : Colors.grey.withOpacity(0.05),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Text(
                          r['description_probleme'] ?? 'Aucune description',
                          style: TextStyle(
                            color: Theme.of(context).textTheme.bodyMedium?.color,
                            height: 1.5,
                          ),
                        ),
                      ),
                    ),
                    
                    const SizedBox(height: 16),
                    
                    // Notes internes
                    _buildSectionWithAction(
                      icon: Icons.sticky_note_2,
                      title: 'Note Interne',
                      actionIcon: Icons.edit,
                      onAction: _showNoteDialog,
                      child: Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: isDark ? Colors.black.withOpacity(0.2) : Colors.grey.withOpacity(0.1),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Text(
                          r['note_interne']?.isNotEmpty == true ? r['note_interne'] : 'Aucune note interne',
                          style: TextStyle(
                            color: Theme.of(context).textTheme.bodySmall?.color,
                            fontStyle: r['note_interne']?.isNotEmpty == true ? FontStyle.normal : FontStyle.italic,
                          ),
                        ),
                      ),
                    ),
                    
                    const SizedBox(height: 16),
                    
                    // Photo
                    _buildPhotoSection(r),
                  ],
                ),
              ),
            ),
            
            // Footer
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: isDark ? const Color(0xFF2C2C2E) : Colors.grey.shade50,
                borderRadius: const BorderRadius.only(
                  bottomLeft: Radius.circular(20),
                  bottomRight: Radius.circular(20),
                ),
                border: Border(top: BorderSide(color: Theme.of(context).dividerColor)),
              ),
              child: Row(
                children: [
                  // SMS Button
                  OutlinedButton.icon(
                    onPressed: _sendSms,
                    icon: const Icon(Icons.sms, size: 18),
                    label: const Text('SMS Client'),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: MacOSTheme.accentPurple,
                      side: const BorderSide(color: MacOSTheme.accentPurple),
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                  ),
                  const Spacer(),
                  TextButton(
                    onPressed: () => Navigator.pop(context),
                    child: const Text('Fermer'),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildInfoGrid(Map<String, dynamic> r) {
    return Column(
      children: [
        Row(
          children: [
            Expanded(child: _InfoItem(icon: Icons.person, label: 'Client', value: '${r['client_nom'] ?? ''} ${r['client_prenom'] ?? ''}')),
            const SizedBox(width: 12),
            Expanded(child: _InfoItem(icon: Icons.phone, label: 'Téléphone', value: r['client_telephone'] ?? 'Non renseigné')),
          ],
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(child: _InfoItem(icon: Icons.smartphone, label: 'Appareil', value: '${r['marque'] ?? ''} ${r['modele'] ?? ''}')),
            const SizedBox(width: 12),
            Expanded(child: _InfoItem(icon: Icons.flag, label: 'Statut', value: _getStatusLabel(r['statut']))),
          ],
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: _InfoItem(
                icon: Icons.euro,
                label: 'Prix',
                value: _getDisplayPrice(r),
                valueColor: MacOSTheme.successGreen,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _InfoItem(
                icon: Icons.calendar_today,
                label: 'Date',
                value: _formatDate(r['date_reception']),
              ),
            ),
          ],
        ),
      ],
    );
  }

  String _getDisplayPrice(Map<String, dynamic> r) {
    double pRep = double.tryParse(r['prix_reparation']?.toString() ?? '0') ?? 0;
    double pOld = double.tryParse(r['prix']?.toString() ?? '0') ?? 0;
    
    // Prefer prix_reparation if set, otherwise prix
    double finalPrice = pRep > 0 ? pRep : pOld;
    return '${finalPrice.toStringAsFixed(2)} €';
  }

  String _getStatusLabel(String? status) {
    final statusMap = {
      'nouveau_diagnostique': 'Nouveau Diagnostique',
      'nouvelle_intervention': 'Nouvelle Intervention',
      'nouvelle_commande': 'Nouvelle Commande',
      'en_cours_diagnostique': 'En cours Diagnostique',
      'en_cours_intervention': 'En cours Intervention',
      'en_attente_accord_client': 'En attente accord',
      'en_attente_livraison': 'En attente livraison',
      'en_attente_responsable': 'En attente responsable',
      'reparation_effectue': 'Réparation effectuée',
      'restitue': 'Restitué',
      'reparation_annule': 'Annulé',
      'devis_accepte': 'Devis accepté',
      'devis_refuse': 'Devis refusé',
    };
    return statusMap[status] ?? status ?? 'Inconnu';
  }

  String _formatDate(dynamic dateValue) {
    if (dateValue == null) return 'Non défini';
    var dateStr = dateValue.toString().trim();
    
    // Fallback on date_creation if date_reception is empty or '-'
    if ((dateStr.isEmpty || dateStr == '-' || dateStr == 'null') && widget.repair['date_creation'] != null) {
      dateStr = widget.repair['date_creation'].toString().trim();
    }
    
    if (dateStr.isEmpty || dateStr == '-' || dateStr == 'null') return 'Non défini';
    
    try {
      final date = DateTime.parse(dateStr);
      return DateFormat('dd/MM/yyyy HH:mm').format(date);
    } catch (e) {
      return dateStr; // Return as-is if parsing fails
    }
  }

  Widget _buildPhotoSection(Map<String, dynamic> r) {
    String? photoPath = r['photo_appareil'] ?? r['photo'] ?? r['photos'];
    
    // Debug
    print('DEBUG PHOTO: photo_appareil=${r['photo_appareil']}, photo=${r['photo']}, photos=${r['photos']}');
    print('DEBUG PHOTO: photoPath=$photoPath');
    
    if (photoPath == null || photoPath.isEmpty) {
      return const SizedBox.shrink();
    }
    
    final authService = Provider.of<AuthService>(context, listen: false);
    // Use the getSubdomain method which retrieves from login input
    final subdomain = authService.getSubdomain();
    
    print('DEBUG PHOTO: Subdomain = $subdomain');
    
    String photoUrl;
    if (photoPath.startsWith('http')) {
      photoUrl = photoPath;
    } else if (photoPath.startsWith('/')) {
      photoUrl = 'https://$subdomain.servo.tools$photoPath';
    } else {
      photoUrl = 'https://$subdomain.servo.tools/$photoPath';
    }
    
    print('DEBUG PHOTO: Final URL = $photoUrl');
    
    return _buildSection(
      icon: Icons.camera_alt,
      title: 'PHOTO APPAREIL',
      child: Container(
        width: double.infinity,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: Theme.of(context).dividerColor),
        ),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(12),
          child: Material(
            color: Colors.transparent,
            child: InkWell(
              onTap: () => _showFullScreenImage(photoUrl),
              child: Stack(
                alignment: Alignment.bottomRight,
                children: [
                  Image.network(
                    photoUrl,
                    height: 200,
                    width: double.infinity,
                    fit: BoxFit.cover,
                    loadingBuilder: (context, child, loadingProgress) {
                      if (loadingProgress == null) return child;
                      return Container(
                        height: 150,
                        color: Colors.grey.withOpacity(0.1),
                        child: const Center(child: CircularProgressIndicator()),
                      );
                    },
                    errorBuilder: (_, __, ___) => Container(
                      height: 100,
                      color: Colors.grey.withOpacity(0.1),
                      child: const Center(child: Icon(Icons.broken_image, size: 48, color: Colors.grey)),
                    ),
                  ),
                  Container(
                    margin: const EdgeInsets.all(8),
                    padding: const EdgeInsets.all(6),
                    decoration: BoxDecoration(
                      color: Colors.black.withOpacity(0.6),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.fullscreen, color: Colors.white, size: 20),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  void _showFullScreenImage(String url) {
    showDialog(
      context: context,
      barrierColor: Colors.black.withOpacity(0.95),
      builder: (context) => Stack(
        children: [
          // Image with Zoom
          Positioned.fill(
            child: InteractiveViewer(
              minScale: 0.5,
              maxScale: 4.0,
              child: Image.network(
                url,
                fit: BoxFit.contain,
              ),
            ),
          ),
          
          // Close Button
          Positioned(
            top: 40,
            right: 40,
            child: Material(
              color: Colors.transparent,
              child: IconButton(
                icon: const Icon(Icons.close, color: Colors.white, size: 30),
                onPressed: () => Navigator.pop(context),
                style: IconButton.styleFrom(
                  backgroundColor: Colors.white.withOpacity(0.2),
                  hoverColor: Colors.red.withOpacity(0.8),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSection({required IconData icon, required String title, required Widget child}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Icon(icon, size: 16, color: const Color(0xFF007AFF)),
            const SizedBox(width: 8),
            Text(
              title,
              style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: Color(0xFF007AFF), letterSpacing: 0.5),
            ),
          ],
        ),
        const SizedBox(height: 12),
        child,
      ],
    );
  }

  Widget _buildSectionWithAction({
    required IconData icon,
    required String title,
    required Widget child,
    required IconData actionIcon,
    required VoidCallback onAction,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Icon(icon, size: 16, color: const Color(0xFF007AFF)),
            const SizedBox(width: 8),
            Text(
              title,
              style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: Color(0xFF007AFF), letterSpacing: 0.5),
            ),
            const Spacer(),
            InkWell(
              onTap: onAction,
              borderRadius: BorderRadius.circular(6),
              child: Container(
                padding: const EdgeInsets.all(6),
                decoration: BoxDecoration(
                  color: const Color(0xFF007AFF).withOpacity(0.1),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Icon(actionIcon, size: 16, color: const Color(0xFF007AFF)),
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        child,
      ],
    );
  }
}

/// Action button for quick actions grid
class _ActionButton extends StatelessWidget {
  final IconData icon;
  final String label;
  final Color color;
  final Color bgColor;
  final VoidCallback onTap;

  const _ActionButton({
    required this.icon,
    required this.label,
    required this.color,
    required this.bgColor,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Material(
      color: bgColor,
      borderRadius: BorderRadius.circular(12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Container(
          width: 100,
          padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 8),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(icon, size: 24, color: color),
              const SizedBox(height: 8),
              Text(label, style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: color)),
            ],
          ),
        ),
      ),
    );
  }
}

/// Info item widget
class _InfoItem extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;
  final Color? valueColor;

  const _InfoItem({required this.icon, required this.label, required this.value, this.valueColor});

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: isDark ? Colors.white.withOpacity(0.05) : Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: Theme.of(context).dividerColor.withOpacity(0.5)),
      ),
      child: Row(
        children: [
          Icon(icon, size: 18, color: const Color(0xFF667eea)),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(label, style: TextStyle(fontSize: 11, color: Theme.of(context).textTheme.bodySmall?.color, fontWeight: FontWeight.w500)),
                const SizedBox(height: 2),
                Text(value, style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: valueColor), overflow: TextOverflow.ellipsis),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

/// Dialog pour envoyer un SMS
class _SmsDialog extends StatefulWidget {
  final Map<String, dynamic> repair;
  final ApiService apiService;

  const _SmsDialog({required this.repair, required this.apiService});

  @override
  State<_SmsDialog> createState() => _SmsDialogState();
}

class _SmsDialogState extends State<_SmsDialog> {
  final _messageController = TextEditingController();
  bool _isSending = false;

  @override
  void initState() {
    super.initState();
    _messageController.text = 
      'Bonjour, votre réparation #${widget.repair['id']} est prête. Merci de passer la récupérer. - GeekBoard';
  }

  Future<void> _sendSms() async {
    if (_messageController.text.isEmpty) return;
    
    setState(() => _isSending = true);
    try {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('SMS envoyé avec succès'), backgroundColor: Colors.green),
      );
      Navigator.pop(context);
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red),
      );
    } finally {
      if (mounted) setState(() => _isSending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      title: const Row(children: [Icon(Icons.sms, color: MacOSTheme.accentPurple), SizedBox(width: 12), Text('Envoyer SMS')]),
      content: SizedBox(
        width: 400,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('À: ${widget.repair['client_telephone']}', style: TextStyle(color: Theme.of(context).textTheme.bodySmall?.color)),
            const SizedBox(height: 16),
            TextField(controller: _messageController, maxLines: 4, decoration: InputDecoration(labelText: 'Message', border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)))),
          ],
        ),
      ),
      actions: [
        TextButton(onPressed: () => Navigator.pop(context), child: const Text('Annuler')),
        ElevatedButton.icon(
          onPressed: _isSending ? null : _sendSms,
          icon: _isSending ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2)) : const Icon(Icons.send, size: 18),
          label: const Text('Envoyer'),
          style: ElevatedButton.styleFrom(backgroundColor: MacOSTheme.accentPurple, foregroundColor: Colors.white),
        ),
      ],
    );
  }

  @override
  void dispose() { _messageController.dispose(); super.dispose(); }
}

/// Dialog pour créer un devis
class _DevisDialog extends StatefulWidget {
  final Map<String, dynamic> repair;
  final ApiService apiService;

  const _DevisDialog({required this.repair, required this.apiService});

  @override
  State<_DevisDialog> createState() => _DevisDialogState();
}

class _DevisDialogState extends State<_DevisDialog> {
  int _currentStep = 0;
  bool _isLoading = false;

  // Step 1: Info
  late TextEditingController _titleController;
  final _descController = TextEditingController();
  final _warrantyController = TextEditingController(text: '3 mois');

  // Step 2: Pannes
  final List<Map<String, dynamic>> _pannes = [];

  // Step 3: Solutions
  final List<Map<String, dynamic>> _solutions = [];

  @override
  void initState() {
    super.initState();
    // Pre-fill title
    _titleController = TextEditingController(
      text: 'Devis réparation ${widget.repair['marque'] ?? ''} ${widget.repair['modele'] ?? ''}'.trim()
    );
    
    // Default items
    _addPanne();
    _addSolution();
  }

  void _addPanne() {
    setState(() {
      _pannes.add({
        'nom': TextEditingController(),
        'description': TextEditingController(),
        'gravite': 'moyenne', // low, medium, high, critical
      });
    });
  }

  void _removePanne(int index) {
    if (_pannes.length > 1) {
      setState(() {
        _pannes[index]['nom'].dispose();
        _pannes[index]['description'].dispose();
        _pannes.removeAt(index);
      });
    }
  }

  void _addSolution() {
    setState(() {
      _solutions.add({
        'nom': TextEditingController(),
        'description': TextEditingController(),
        'garantie': TextEditingController(text: '3 mois'),
        'prix': TextEditingController(text: ''),
        'duree': TextEditingController(text: ''),
      });
    });
  }

  void _removeSolution(int index) {
    if (_solutions.length > 1) {
      setState(() {
        _solutions[index]['nom'].dispose();
        _solutions[index]['description'].dispose();
        _solutions[index]['garantie'].dispose();
        _solutions[index]['prix'].dispose();
        _solutions[index]['duree'].dispose();
        _solutions.removeAt(index);
      });
    }
  }

  Future<void> _submit() async {
    // Validation
    if (_titleController.text.isEmpty) {
      _showError('Le titre est obligatoire');
      return;
    }
    
    // Prepare Data
    final pannesData = _pannes.map((p) => {
      'nom': p['nom'].text,
      'description': p['description'].text,
      'gravite': p['gravite'],
    }).toList();

    final solutionsData = _solutions.map((s) => {
      'nom': s['nom'].text,
      'description': s['description'].text,
      'garantie': s['garantie'].text,
      'prix': double.tryParse(s['prix'].text.replaceAll(',', '.')) ?? 0.0,
      'duree': s['duree'].text,
    }).where((s) => (s['nom'] as String).isNotEmpty && (s['prix'] as double) > 0).toList();

    if (solutionsData.isEmpty) {
      _showError('Veuillez ajouter au moins une solution avec un prix valide');
      return;
    }

    setState(() => _isLoading = true);

    try {
      final response = await widget.apiService.post(ApiConfig.devisCreateEndpoint, {
        'reparation_id': widget.repair['id'],
        'titre': _titleController.text,
        'description': _descController.text,
        'garantie': _warrantyController.text,
        'pannes': pannesData,
        'solutions': solutionsData,
      });

      if (mounted) {
        Navigator.pop(context);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Devis créé et envoyé avec succès !'), backgroundColor: Colors.green),
        );
      }
    } catch (e) {
      if (mounted) {
        _showError('Erreur: $e');
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _showError(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message), backgroundColor: Colors.red),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    return AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      title: const Row(children: [Icon(Icons.receipt_long, color: Color(0xFF7c3aed)), SizedBox(width: 12), Text('Créer un devis')]),
      content: SizedBox(
        width: 550,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Stepper Header
             Padding(
              padding: const EdgeInsets.only(bottom: 16.0),
              child: Row(
                children: [
                  _buildStepIndicator(0, 'Informations', isDark),
                  _buildStepLine(0, isDark),
                  _buildStepIndicator(1, 'Pannes', isDark),
                  _buildStepLine(1, isDark),
                  _buildStepIndicator(2, 'Solutions', isDark),
                ],
              ),
            ),
            
            ConstrainedBox(
              constraints: const BoxConstraints(maxHeight: 350),
              child: SingleChildScrollView(
                child: _buildStepContent(isDark),
              ),

            ),
          ],
        ),
      ),
      actions: [
        if (_currentStep > 0)
          TextButton(
            onPressed: () => setState(() => _currentStep--),
            child: const Text('Précédent'),
          ),
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: const Text('Annuler'),
        ),
        if (_currentStep < 2)
          ElevatedButton(
            onPressed: () => setState(() => _currentStep++),
            style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF7c3aed)),
            child: const Text('Suivant', style: TextStyle(color: Colors.white)),
          )
        else
          ElevatedButton(
            onPressed: _isLoading ? null : _submit,
            style: ElevatedButton.styleFrom(backgroundColor: Colors.green),
            child: _isLoading 
              ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
              : const Text('Créer et Envoyer', style: TextStyle(color: Colors.white)),
          ),
      ],
    );
  }

  Widget _buildStepIndicator(int step, String label, bool isDark) {
    final isActive = _currentStep == step;
    final isCompleted = _currentStep > step;
    final color = isCompleted ? Colors.green : (isActive ? const Color(0xFF7c3aed) : Colors.grey);
    
    return Expanded(
      child: Column(
        children: [
          Container(
            width: 32,
            height: 32,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: isCompleted ? Colors.green : (isActive ? const Color(0xFF7c3aed) : Colors.transparent),
              border: Border.all(color: color, width: 2),
            ),
            child: Center(
              child: isCompleted 
                ? const Icon(Icons.check, size: 16, color: Colors.white)
                : Text('${step + 1}', style: TextStyle(color: isActive ? Colors.white : color, fontWeight: FontWeight.bold)),
            ),
          ),
          const SizedBox(height: 8),
          Text(label, style: TextStyle(color: color, fontWeight: isActive ? FontWeight.bold : FontWeight.normal, fontSize: 12)),
        ],
      ),
    );
  }
  
  Widget _buildStepLine(int step, bool isDark) {
    return Container(
      width: 40,
      height: 2,
      color: _currentStep > step ? Colors.green : Colors.grey.withOpacity(0.3),
      margin: const EdgeInsets.symmetric(horizontal: 8, vertical: 20), // Align with circle center (approx)
    );
  }

  Widget _buildStepContent(bool isDark) {
    switch (_currentStep) {
      case 0:
        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildTextField(controller: _titleController, label: 'Titre du devis', icon: Icons.title),
            const SizedBox(height: 16),
            _buildTextField(controller: _descController, label: 'Description générale', icon: Icons.description, maxLines: 3),
            const SizedBox(height: 16),
            _buildTextField(controller: _warrantyController, label: 'Garantie globale', icon: Icons.verified),
          ],
        );
      case 1:
        return Column(
          children: [
            ..._pannes.asMap().entries.map((entry) {
              final index = entry.key;
              final panne = entry.value;
              return Card(
                color: isDark ? const Color(0xFF1E1E1E) : Colors.grey.shade50,
                margin: const EdgeInsets.only(bottom: 16),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text('Panne #${index + 1}', style: const TextStyle(fontWeight: FontWeight.bold)),
                          if (_pannes.length > 1)
                            IconButton(icon: const Icon(Icons.delete, color: Colors.red, size: 20), onPressed: () => _removePanne(index)),
                        ],
                      ),
                      const SizedBox(height: 16),
                      _buildTextField(controller: panne['nom'], label: 'Nom de la panne', icon: Icons.bug_report),
                      const SizedBox(height: 12),
                      _buildTextField(controller: panne['description'], label: 'Description', icon: Icons.notes, maxLines: 2),
                      const SizedBox(height: 12),
                      DropdownButtonFormField<String>(
                        value: panne['gravite'],
                        decoration: InputDecoration(
                          labelText: 'Gravité',
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                          prefixIcon: const Icon(Icons.warning_amber),
                        ),
                        items: const [
                          DropdownMenuItem(value: 'faible', child: Text('Faible')),
                          DropdownMenuItem(value: 'moyenne', child: Text('Moyenne')),
                          DropdownMenuItem(value: 'elevee', child: Text('Élevée')),
                          DropdownMenuItem(value: 'critique', child: Text('Critique')),
                        ],
                        onChanged: (v) => setState(() => panne['gravite'] = v),
                      ),
                    ],
                  ),
                ),
              );
            }),
            OutlinedButton.icon(
              onPressed: _addPanne,
              icon: const Icon(Icons.add),
              label: const Text('Ajouter une panne'),
            ),
          ],
        );
      case 2:
        return Column(
          children: [
            ..._solutions.asMap().entries.map((entry) {
              final index = entry.key;
              final solution = entry.value;
              return Card(
                color: isDark ? const Color(0xFF1E1E1E) : Colors.grey.shade50,
                margin: const EdgeInsets.only(bottom: 16),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text('Solution #${index + 1}', style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.green)),
                          if (_solutions.length > 1)
                            IconButton(icon: const Icon(Icons.delete, color: Colors.red, size: 20), onPressed: () => _removeSolution(index)),
                        ],
                      ),
                      const SizedBox(height: 16),
                      Row(
                        children: [
                          Expanded(flex: 2, child: _buildTextField(controller: solution['nom'], label: 'Nom de la solution', icon: Icons.build)),
                          const SizedBox(width: 12),
                          Expanded(child: _buildTextField(controller: solution['prix'], label: 'Prix (€)', icon: Icons.euro, keyboardType: TextInputType.number)),
                        ],
                      ),
                      const SizedBox(height: 12),
                      _buildTextField(controller: solution['description'], label: 'Description', icon: Icons.notes, maxLines: 2),
                      const SizedBox(height: 12),
                      Row(
                        children: [
                          Expanded(child: _buildTextField(controller: solution['garantie'], label: 'Garantie', icon: Icons.verified)),
                          const SizedBox(width: 12),
                          Expanded(child: _buildTextField(controller: solution['duree'], label: 'Durée', icon: Icons.timer)),
                        ],
                      ),
                    ],
                  ),
                ),
              );
            }),
            OutlinedButton.icon(
              onPressed: _addSolution,
              icon: const Icon(Icons.add),
              label: const Text('Ajouter une solution'),
            ),
          ],
        );
      default:
        return const SizedBox.shrink();
    }
  }

  Widget _buildTextField({
    required TextEditingController controller,
    required String label,
    required IconData icon,
    int maxLines = 1,
    TextInputType? keyboardType,
  }) {
    return TextField(
      controller: controller,
      maxLines: maxLines,
      keyboardType: keyboardType,
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: Icon(icon, size: 20),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      ),
    );
  }

  @override
  void dispose() {
    _titleController.dispose();
    _descController.dispose();
    _warrantyController.dispose();
    for (var p in _pannes) {
      if (p['nom'] is TextEditingController) p['nom'].dispose();
      if (p['description'] is TextEditingController) p['description'].dispose();
    }
    for (var s in _solutions) {
      if (s['nom'] is TextEditingController) s['nom'].dispose();
      if (s['description'] is TextEditingController) s['description'].dispose();
      if (s['garantie'] is TextEditingController) s['garantie'].dispose();
      if (s['prix'] is TextEditingController) s['prix'].dispose();
      if (s['duree'] is TextEditingController) s['duree'].dispose();
    }
    super.dispose();
  }
}


/// Dialog pour modifier le statut - Version complète avec catégories
class _StatusDialog extends StatefulWidget {
  final Map<String, dynamic> repair;
  final ApiService apiService;
  final VoidCallback? onUpdate;

  const _StatusDialog({required this.repair, required this.apiService, this.onUpdate});

  @override
  State<_StatusDialog> createState() => _StatusDialogState();
}

class _StatusDialogState extends State<_StatusDialog> {
  bool _sendSms = true; // Par défaut activé

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    final categories = [
      {
        'name': 'Nouvelle',
        'color': Colors.blue,
        'statuses': [
          {'id': 'nouveau_diagnostique', 'label': 'Nouveau diagnostique'},
          {'id': 'nouvelle_intervention', 'label': 'Nouvelle intervention'},
          {'id': 'nouvelle_commande', 'label': 'Nouvelle commande'},
          {'id': 'devis_accepte', 'label': 'Devis accepté'},
          {'id': 'devis_refuse', 'label': 'Devis refusé'},
        ],
      },
      {
        'name': 'En cours',
        'color': Colors.orange,
        'statuses': [
          {'id': 'en_cours_intervention', 'label': 'En cours d\'intervention'},
          {'id': 'en_cours_diagnostique', 'label': 'En cours diagnostique'},
        ],
      },
      {
        'name': 'En attente',
        'color': Colors.amber,
        'statuses': [
          {'id': 'en_attente_livraison', 'label': 'En attente de livraison'},
          {'id': 'en_attente_accord_client', 'label': 'En attente accord client'},
          {'id': 'en_attente_responsable', 'label': 'En attente d\'un responsable'},
          {'id': 'retard_livraison', 'label': 'Retard de livraison'},
        ],
      },
      {
        'name': 'Terminé',
        'color': Colors.green,
        'statuses': [
          {'id': 'reparation_effectue', 'label': 'Réparation effectuée'},
          {'id': 'reparation_annule', 'label': 'Réparation annulée'},
        ],
      },
      {
        'name': 'Archivé',
        'color': Colors.grey,
        'statuses': [
          {'id': 'restitue', 'label': 'Restitué'},
          {'id': 'annule', 'label': 'Annulé'},
          {'id': 'gardiennage', 'label': 'Gardiennage'},
        ],
      },
    ];

    return AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      title: Row(
        children: [
          const Icon(Icons.swap_horiz, color: Color(0xFF16a34a)),
          const SizedBox(width: 12),
          const Expanded(child: Text('Modifier statut')),
          // SMS Toggle
          Tooltip(
            message: _sendSms ? 'SMS activé' : 'SMS désactivé',
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(
                  Icons.sms,
                  size: 18,
                  color: _sendSms ? Colors.green : Colors.grey,
                ),
                const SizedBox(width: 4),
                Switch(
                  value: _sendSms,
                  onChanged: (v) => setState(() => _sendSms = v),
                  activeColor: Colors.green,
                  materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                ),
              ],
            ),
          ),
        ],
      ),
      content: SizedBox(
        width: 400,
        height: 500,
        child: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: categories.map((category) {
              final catColor = category['color'] as Color;
              final statuses = category['statuses'] as List<Map<String, String>>;
              
              return Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Category Header
                  Container(
                    margin: const EdgeInsets.only(top: 12, bottom: 8),
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: catColor.withOpacity(0.15),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      category['name'] as String,
                      style: TextStyle(
                        fontWeight: FontWeight.bold,
                        color: catColor,
                        fontSize: 13,
                      ),
                    ),
                  ),
                  // Status buttons
                  ...statuses.map((s) => Padding(
                    padding: const EdgeInsets.only(bottom: 6),
                    child: SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: () async {
                          try {
                            await widget.apiService.post(ApiConfig.reparationsUpdateSpecificStatusEndpoint, {
                                'repair_id': widget.repair['id'], 
                                'status_id': s['id'],
                                'send_sms': _sendSms
                            });
                            widget.onUpdate?.call();
                            Navigator.pop(context);
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(
                                content: Text('Statut mis à jour: ${s['label']}${_sendSms ? ' (SMS envoyé)' : ''}'),
                                backgroundColor: catColor,
                              )
                            );
                          } catch (e) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red)
                            );
                          }
                        },
                        style: ElevatedButton.styleFrom(
                          backgroundColor: catColor,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 16),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                        ),
                        child: Text(s['label']!, style: const TextStyle(fontSize: 13)),
                      ),
                    ),
                  )),
                ],
              );
            }).toList(),
          ),
        ),
      ),
    );
  }
}


/// Dialog pour modifier le prix
class _PriceDialog extends StatefulWidget {
  final Map<String, dynamic> repair;
  final ApiService apiService;
  final VoidCallback? onUpdate;

  const _PriceDialog({required this.repair, required this.apiService, this.onUpdate});

  @override
  State<_PriceDialog> createState() => _PriceDialogState();
}

class _PriceDialogState extends State<_PriceDialog> {
  late TextEditingController _priceController;
  bool _isSaving = false;

  @override
  void initState() {
    super.initState();
    _priceController = TextEditingController(text: widget.repair['prix_reparation']?.toString() ?? widget.repair['prix']?.toString() ?? '0.00');
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      title: const Row(children: [Icon(Icons.euro, color: Color(0xFFd97706)), SizedBox(width: 12), Text('Modifier le prix')]),
      content: TextField(
        controller: _priceController,
        keyboardType: TextInputType.number,
        decoration: InputDecoration(
          labelText: 'Prix (€)',
          suffixText: '€',
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
        ),
      ),
      actions: [
        TextButton(onPressed: () => Navigator.pop(context), child: const Text('Annuler')),
        ElevatedButton(
          onPressed: _isSaving ? null : () async {
            setState(() => _isSaving = true);
            try {
              await widget.apiService.post(ApiConfig.reparationsUpdatePriceEndpoint, {
                  'reparation_id': widget.repair['id'], 
                  'price': _priceController.text
              });
              // Mettre à jour les données locales pour que l'affichage se rafraîchisse
              widget.repair['prix_reparation'] = _priceController.text;
              widget.repair['prix'] = _priceController.text;
              widget.onUpdate?.call();
              Navigator.pop(context);
              ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Prix mis à jour: ${_priceController.text} €'), backgroundColor: Colors.green));
            } catch (e) {
              ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red));
            } finally {
              if (mounted) setState(() => _isSaving = false);
            }
          },
          style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFd97706)),
          child: _isSaving ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : const Text('Enregistrer', style: TextStyle(color: Colors.white)),
        ),
      ],
    );
  }

  @override
  void dispose() { _priceController.dispose(); super.dispose(); }
}

/// Dialog pour ajouter une commande
class _OrderDialog extends StatelessWidget {
  final Map<String, dynamic> repair;
  final ApiService apiService;

  const _OrderDialog({required this.repair, required this.apiService});

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      title: const Row(children: [Icon(Icons.shopping_cart, color: Color(0xFF0891b2)), SizedBox(width: 12), Text('Ajouter commande')]),
      content: Text('Ajouter une pièce à commander pour la réparation #${repair['id']}'),
      actions: [
        TextButton(onPressed: () => Navigator.pop(context), child: const Text('Fermer')),
        ElevatedButton(
          onPressed: () { Navigator.pop(context); ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Fonctionnalité à venir'), backgroundColor: Colors.blue)); },
          style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF0891b2)),
          child: const Text('Commander', style: TextStyle(color: Colors.white)),
        ),
      ],
    );
  }
}

/// Dialog pour voir l'historique complet de la réparation
class _HistoryDialog extends StatefulWidget {
  final Map<String, dynamic> repair;
  final ApiService apiService;

  const _HistoryDialog({required this.repair, required this.apiService});

  @override
  State<_HistoryDialog> createState() => _HistoryDialogState();
}

class _HistoryDialogState extends State<_HistoryDialog> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  Map<String, dynamic>? _historyData;
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    _loadHistory();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _loadHistory() async {
    try {
      final response = await widget.apiService.get(
        '${ApiConfig.reparationsHistoryEndpoint}?id=${widget.repair['id']}'
      );
      if (response['success'] == true) {
        setState(() {
          _historyData = response;
          _isLoading = false;
        });
      } else {
        setState(() {
          _error = response['error'] ?? 'Erreur de chargement';
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    return AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      title: Row(
        children: [
          const Icon(Icons.history, color: Color(0xFFb45309)),
          const SizedBox(width: 12),
          Text('Historique #${widget.repair['id']}'),
        ],
      ),
      content: SizedBox(
        width: 550,
        height: 500,
        child: _isLoading
            ? const Center(child: CircularProgressIndicator())
            : _error != null
                ? Center(child: Text('Erreur: $_error', style: const TextStyle(color: Colors.red)))
                : _buildContent(isDark),
      ),
      actions: [
        TextButton(onPressed: () => Navigator.pop(context), child: const Text('Fermer')),
      ],
    );
  }

  Widget _buildContent(bool isDark) {
    return Column(
      children: [
        // Tabs
        Container(
          decoration: BoxDecoration(
            color: isDark ? Colors.grey.shade800 : Colors.grey.shade100,
            borderRadius: BorderRadius.circular(10),
          ),
          child: TabBar(
            controller: _tabController,
            labelColor: Colors.white,
            unselectedLabelColor: isDark ? Colors.grey.shade400 : Colors.grey.shade600,
            indicator: BoxDecoration(
              color: const Color(0xFFb45309),
              borderRadius: BorderRadius.circular(8),
            ),
            indicatorSize: TabBarIndicatorSize.tab,
            tabs: const [
              Tab(icon: Icon(Icons.sync_alt, size: 18), text: 'Statuts'),
              Tab(icon: Icon(Icons.sms, size: 18), text: 'SMS'),
              Tab(icon: Icon(Icons.person, size: 18), text: 'Client'),
            ],
          ),
        ),
        const SizedBox(height: 12),
        // Tab Content
        Expanded(
          child: TabBarView(
            controller: _tabController,
            children: [
              _buildStatusTab(isDark),
              _buildSmsTab(isDark),
              _buildClientRepairsTab(isDark),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildStatusTab(bool isDark) {
    final statusHistory = (_historyData?['status_history'] as List?) ?? [];
    
    if (statusHistory.isEmpty) {
      return _buildEmptyState(Icons.sync_alt, 'Aucun changement de statut');
    }
    
    return ListView.builder(
      itemCount: statusHistory.length,
      itemBuilder: (context, index) {
        final status = statusHistory[index];
        return Container(
          margin: const EdgeInsets.only(bottom: 8),
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: isDark ? Colors.grey.shade800 : Colors.white,
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: isDark ? Colors.grey.shade700 : Colors.grey.shade200),
          ),
          child: Row(
            children: [
              Container(
                width: 36,
                height: 36,
                decoration: BoxDecoration(
                  color: const Color(0xFFb45309).withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Icon(Icons.sync_alt, color: Color(0xFFb45309), size: 18),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      status['action_type']?.toString().replaceAll('_', ' ').toUpperCase() ?? 'Action',
                      style: TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 13,
                        color: isDark ? Colors.white : Colors.black87,
                      ),
                    ),
                    if (status['details'] != null && status['details'].toString().isNotEmpty)
                      Text(
                        status['details'],
                        style: TextStyle(fontSize: 12, color: isDark ? Colors.grey.shade400 : Colors.grey.shade600),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        Text(
                          status['date_formatted'] ?? '',
                          style: TextStyle(fontSize: 11, color: isDark ? Colors.grey.shade500 : Colors.grey.shade500),
                        ),
                        const SizedBox(width: 8),
                        Text(
                          '• ${status['user_name'] ?? 'Système'}',
                          style: TextStyle(fontSize: 11, color: isDark ? Colors.grey.shade500 : Colors.grey.shade500),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildSmsTab(bool isDark) {
    final smsHistory = (_historyData?['sms_history'] as List?) ?? [];
    
    if (smsHistory.isEmpty) {
      return _buildEmptyState(Icons.sms, 'Aucun SMS envoyé');
    }
    
    return ListView.builder(
      itemCount: smsHistory.length,
      itemBuilder: (context, index) {
        final sms = smsHistory[index];
        final isSuccess = sms['status'] == 1 || sms['status'] == '1';
        
        return Container(
          margin: const EdgeInsets.only(bottom: 8),
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: isDark ? Colors.grey.shade800 : Colors.white,
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: isDark ? Colors.grey.shade700 : Colors.grey.shade200),
          ),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 36,
                height: 36,
                decoration: BoxDecoration(
                  color: (isSuccess ? Colors.green : Colors.red).withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Icon(
                  isSuccess ? Icons.check_circle : Icons.error,
                  color: isSuccess ? Colors.green : Colors.red,
                  size: 18,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          sms['recipient'] ?? '',
                          style: TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 13,
                            color: isDark ? Colors.white : Colors.black87,
                          ),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                          decoration: BoxDecoration(
                            color: (isSuccess ? Colors.green : Colors.red).withOpacity(0.1),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: Text(
                            sms['status_text'] ?? (isSuccess ? 'Envoyé' : 'Échec'),
                            style: TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.bold,
                              color: isSuccess ? Colors.green : Colors.red,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(
                      sms['message'] ?? '',
                      style: TextStyle(fontSize: 12, color: isDark ? Colors.grey.shade400 : Colors.grey.shade600),
                      maxLines: 3,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 4),
                    Text(
                      sms['date_formatted'] ?? '',
                      style: TextStyle(fontSize: 11, color: isDark ? Colors.grey.shade500 : Colors.grey.shade500),
                    ),
                  ],
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildClientRepairsTab(bool isDark) {
    final clientRepairs = (_historyData?['client_repairs'] as List?) ?? [];
    
    if (clientRepairs.isEmpty) {
      return _buildEmptyState(Icons.build, 'Aucune autre réparation pour ce client');
    }
    
    return ListView.builder(
      itemCount: clientRepairs.length,
      itemBuilder: (context, index) {
        final repair = clientRepairs[index];
        
        return Container(
          margin: const EdgeInsets.only(bottom: 8),
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: isDark ? Colors.grey.shade800 : Colors.white,
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: isDark ? Colors.grey.shade700 : Colors.grey.shade200),
          ),
          child: Row(
            children: [
              Container(
                width: 36,
                height: 36,
                decoration: BoxDecoration(
                  color: Colors.blue.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Icon(Icons.build, color: Colors.blue, size: 18),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          '#${repair['id']}',
                          style: TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 13,
                            color: isDark ? Colors.white : Colors.black87,
                          ),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                          decoration: BoxDecoration(
                            color: Colors.blue.withOpacity(0.1),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: Text(
                            repair['statut']?.toString().replaceAll('_', ' ') ?? '',
                            style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.blue),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(
                      '${repair['marque'] ?? ''} ${repair['modele'] ?? ''}'.trim(),
                      style: TextStyle(fontSize: 12, color: isDark ? Colors.grey.shade400 : Colors.grey.shade600),
                    ),
                    Text(
                      repair['description_probleme'] ?? '',
                      style: TextStyle(fontSize: 11, color: isDark ? Colors.grey.shade500 : Colors.grey.shade500),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 4),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          repair['date_formatted'] ?? '',
                          style: TextStyle(fontSize: 11, color: isDark ? Colors.grey.shade500 : Colors.grey.shade500),
                        ),
                        if (repair['prix_reparation'] != null)
                          Text(
                            '${repair['prix_reparation']} €',
                            style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.green),
                          ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildEmptyState(IconData icon, String message) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(icon, size: 48, color: Colors.grey.shade400),
          const SizedBox(height: 12),
          Text(message, style: TextStyle(color: Colors.grey.shade500, fontSize: 14)),
        ],
      ),
    );
  }
}


/// Dialog pour aperçu et impression d'étiquette
class _LabelPreviewDialog extends StatefulWidget {
  final Map<String, dynamic> repair;
  final ApiService apiService;

  const _LabelPreviewDialog({required this.repair, required this.apiService});

  @override
  State<_LabelPreviewDialog> createState() => _LabelPreviewDialogState();
}

class _LabelPreviewDialogState extends State<_LabelPreviewDialog> {
  Map<String, dynamic>? _labelData;
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadLabelData();
  }

  Future<void> _loadLabelData() async {
    try {
      final response = await widget.apiService.get(
        '${ApiConfig.reparationsLabelEndpoint}?id=${widget.repair['id']}'
      );
      if (response['success'] == true && response['label'] != null) {
        setState(() {
          _labelData = response['label'];
          _isLoading = false;
        });
      } else {
        setState(() {
          _error = response['error'] ?? 'Erreur de chargement';
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  Future<void> _printLabel() async {
    if (_labelData == null) return;
    
    try {
      await Printing.layoutPdf(
        onLayout: (format) async => _generatePdf(format),
        name: 'Etiquette_${_labelData!['id']}',
      );
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Erreur impression: $e'), backgroundColor: Colors.red),
      );
    }
  }

  Future<Uint8List> _generatePdf(PdfPageFormat format) async {
    final pdf = pw.Document();
    final data = _labelData!;

    pdf.addPage(
      pw.Page(
        pageFormat: PdfPageFormat.a6,
        margin: const pw.EdgeInsets.all(10),
        build: (pw.Context context) {
          return pw.Container(
            padding: const pw.EdgeInsets.all(15),
            decoration: pw.BoxDecoration(
              border: pw.Border.all(color: PdfColors.black, width: 2),
              borderRadius: pw.BorderRadius.circular(8),
            ),
            child: pw.Column(
              crossAxisAlignment: pw.CrossAxisAlignment.start,
              children: [
                // Header avec company name et N° réparation
                pw.Row(
                  mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                  children: [
                    pw.Text(
                      data['company_name'] ?? 'SERVO',
                      style: pw.TextStyle(fontSize: 16, fontWeight: pw.FontWeight.bold),
                    ),
                    pw.Container(
                      padding: const pw.EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: pw.BoxDecoration(
                        color: PdfColors.black,
                        borderRadius: pw.BorderRadius.circular(4),
                      ),
                      child: pw.Text(
                        '#${data['id']}',
                        style: pw.TextStyle(color: PdfColors.white, fontSize: 14, fontWeight: pw.FontWeight.bold),
                      ),
                    ),
                  ],
                ),
                pw.SizedBox(height: 10),
                pw.Divider(),
                pw.SizedBox(height: 8),
                
                // Client info
                pw.Text('CLIENT', style: pw.TextStyle(fontSize: 10, fontWeight: pw.FontWeight.bold, color: PdfColors.black)),
                pw.Text(
                  '${data['client_prenom'] ?? ''} ${data['client_nom'] ?? ''}'.trim(),
                  style: pw.TextStyle(fontSize: 12, fontWeight: pw.FontWeight.bold),
                ),
                pw.Text(data['client_telephone'] ?? '', style: const pw.TextStyle(fontSize: 11)),
                pw.SizedBox(height: 10),
                
                // Appareil info
                pw.Text('APPAREIL', style: pw.TextStyle(fontSize: 10, fontWeight: pw.FontWeight.bold, color: PdfColors.black)),
                pw.Text(
                  '${data['marque'] ?? ''} ${data['modele'] ?? ''}'.trim(),
                  style: pw.TextStyle(fontSize: 12, fontWeight: pw.FontWeight.bold),
                ),
                if (data['numero_serie'] != null && data['numero_serie'].toString().isNotEmpty)
                  pw.Text('S/N: ${data['numero_serie']}', style: const pw.TextStyle(fontSize: 9)),
                pw.SizedBox(height: 10),
                
                // Problème
                pw.Text('PROBLÈME', style: pw.TextStyle(fontSize: 10, fontWeight: pw.FontWeight.bold, color: PdfColors.black)),
                pw.Text(
                  data['description_probleme'] ?? '',
                  style: const pw.TextStyle(fontSize: 10),
                  maxLines: 3,
                ),
                pw.SizedBox(height: 10),
                
                // Date et Prix
                pw.Row(
                  mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                  children: [
                    pw.Column(
                      crossAxisAlignment: pw.CrossAxisAlignment.start,
                      children: [
                        pw.Text('DATE', style: pw.TextStyle(fontSize: 9, fontWeight: pw.FontWeight.bold, color: PdfColors.black)),
                        pw.Text(data['date_formatted'] ?? '', style: const pw.TextStyle(fontSize: 10)),
                      ],
                    ),
                    pw.Column(
                      crossAxisAlignment: pw.CrossAxisAlignment.end,
                      children: [
                        pw.Text('PRIX', style: pw.TextStyle(fontSize: 9, fontWeight: pw.FontWeight.bold, color: PdfColors.black)),
                        pw.Text(
                          '${data['prix_reparation'] ?? '0.00'} €',
                          style: pw.TextStyle(fontSize: 12, fontWeight: pw.FontWeight.bold),
                        ),
                      ],
                    ),
                  ],
                ),
                pw.SizedBox(height: 15),
                
                // QR Code placeholder (barcode package can generate it)
                pw.Center(
                  child: pw.BarcodeWidget(
                    barcode: pw.Barcode.qrCode(),
                    data: data['qr_data'] ?? 'REP-${data['id']}',
                    width: 60,
                    height: 60,
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );

    return pdf.save();
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    return AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      title: Row(
        children: [
          const Icon(Icons.print, color: Color(0xFF0891b2)),
          const SizedBox(width: 12),
          Text('Étiquette #${widget.repair['id']}'),
        ],
      ),
      content: SizedBox(
        width: 350,
        height: 450,
        child: _isLoading
            ? const Center(child: CircularProgressIndicator())
            : _error != null
                ? Center(child: Text('Erreur: $_error', style: const TextStyle(color: Colors.red)))
                : _buildLabelPreview(isDark),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: const Text('Fermer'),
        ),
        if (!_isLoading && _error == null)
          ElevatedButton.icon(
            onPressed: _printLabel,
            icon: const Icon(Icons.print, size: 18),
            label: const Text('Imprimer'),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF0891b2),
              foregroundColor: Colors.white,
            ),
          ),
      ],
    );
  }

  Widget _buildLabelPreview(bool isDark) {
    if (_labelData == null) return const SizedBox();
    final data = _labelData!;
    
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey.shade300, width: 2),
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.1), blurRadius: 10)],
      ),
      padding: const EdgeInsets.all(16),
      child: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  data['company_name'] ?? 'SERVO',
                  style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.black),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                  decoration: BoxDecoration(
                    color: const Color(0xFF0891b2),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    '#${data['id']}',
                    style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14),
                  ),
                ),
              ],
            ),
            const Divider(height: 20),
            
            // Client
            _buildSection('CLIENT', '${data['client_prenom'] ?? ''} ${data['client_nom'] ?? ''}'.trim()),
            Text(data['client_telephone'] ?? '', style: const TextStyle(color: Colors.black54, fontSize: 12)),
            const SizedBox(height: 12),
            
            // Appareil
            _buildSection('APPAREIL', '${data['marque'] ?? ''} ${data['modele'] ?? ''}'.trim()),
            if (data['numero_serie'] != null && data['numero_serie'].toString().isNotEmpty)
              Text('S/N: ${data['numero_serie']}', style: const TextStyle(color: Colors.black54, fontSize: 11)),
            const SizedBox(height: 12),
            
            // Problème
            _buildSection('PROBLÈME', ''),
            Text(
              data['description_probleme'] ?? '',
              style: const TextStyle(fontSize: 12, color: Colors.black87),
              maxLines: 3,
              overflow: TextOverflow.ellipsis,
            ),
            const SizedBox(height: 12),
            
            // Date et Prix
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('DATE', style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.black)),
                    Text(data['date_formatted'] ?? '', style: const TextStyle(fontSize: 12, color: Colors.black)),
                  ],
                ),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text('PRIX', style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.black)),
                    Text(
                      '${data['prix_reparation'] ?? '0.00'} €',
                      style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.black),
                    ),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 16),
            
            // QR Code
            Center(
              child: QrImageView(
                data: data['qr_data'] ?? 'REP-${data['id']}',
                version: QrVersions.auto,
                size: 80,
                backgroundColor: Colors.white,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSection(String title, String content) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(title, style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.black)),
        if (content.isNotEmpty)
          Text(content, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Colors.black)),
      ],
    );
  }
}

/// Dialog pour modifier la note interne
class _NoteDialog extends StatefulWidget {
  final Map<String, dynamic> repair;
  final ApiService apiService;
  final VoidCallback? onUpdate;

  const _NoteDialog({required this.repair, required this.apiService, this.onUpdate});

  @override
  State<_NoteDialog> createState() => _NoteDialogState();
}

class _NoteDialogState extends State<_NoteDialog> {
  late TextEditingController _noteController;
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _noteController = TextEditingController(text: widget.repair['note_interne'] ?? '');
  }

  @override
  void dispose() {
    _noteController.dispose();
    super.dispose();
  }

  Future<void> _saveNote() async {
    setState(() => _isLoading = true);
    
    try {
      final response = await widget.apiService.post(
        ApiConfig.reparationsUpdateEndpoint,
        {
          'id': widget.repair['id'],
          'note_interne': _noteController.text,
        },
      );
      
      if (mounted) {
        widget.repair['note_interne'] = _noteController.text;
        widget.onUpdate?.call();
        Navigator.pop(context);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Note mise à jour'), backgroundColor: Colors.green),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    return AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      title: Row(
        children: [
          const Icon(Icons.edit_note, color: Color(0xFF667eea)),
          const SizedBox(width: 12),
          Text('Note interne #${widget.repair['id']}'),
        ],
      ),
      content: SizedBox(
        width: 400,
        child: TextField(
          controller: _noteController,
          maxLines: 6,
          decoration: InputDecoration(
            hintText: 'Entrez une note interne...',
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
            fillColor: isDark ? Colors.grey.shade800 : Colors.grey.shade100,
            filled: true,
          ),
        ),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: const Text('Annuler'),
        ),
        ElevatedButton.icon(
          onPressed: _isLoading ? null : _saveNote,
          icon: _isLoading 
              ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))
              : const Icon(Icons.save, size: 18),
          label: const Text('Enregistrer'),
          style: ElevatedButton.styleFrom(
            backgroundColor: const Color(0xFF667eea),
            foregroundColor: Colors.white,
          ),
        ),
      ],
    );
  }
}
