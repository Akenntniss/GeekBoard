import 'package:flutter/material.dart';
import 'package:flutter/cupertino.dart';
import 'package:provider/provider.dart';
import 'package:qr_flutter/qr_flutter.dart';
import 'package:image_picker/image_picker.dart';
import 'package:flutter_colorpicker/flutter_colorpicker.dart';
import '../../services/api_service.dart';
import '../../services/auth_service.dart';
import '../../config/api_config.dart';
import '../../theme/macos_theme.dart';
import '../../widgets/app_shell.dart';

class ScreenDetailScreen extends StatefulWidget {
  final int screenId;
  final String screenName;

  const ScreenDetailScreen({super.key, required this.screenId, required this.screenName});

  @override
  State<ScreenDetailScreen> createState() => _ScreenDetailScreenState();
}

class _ScreenDetailScreenState extends State<ScreenDetailScreen> {
  bool _isLoading = true;
  Map<String, dynamic>? _screenData;
  List<dynamic> _slides = [];
  
  // Settings Local State
  String _currentDeviceType = 'tablet';
  String _currentOrientation = 'landscape';
  bool _slideshowEnabled = true;
  int _slideDuration = 5;
  int? _selectedSlideId;

  List<Map<String, dynamic>> _shopUsers = [];
  List<int> _assignedUserIds = [];

  @override
  void initState() {
    super.initState();
    _loadDetails();
  }

  Future<void> _loadDetails() async {
    setState(() => _isLoading = true);
    try {
      final data = await context.read<ApiService>().getScreenDetails(widget.screenId);
      final users = await context.read<ApiService>().getShopUsers();
      
      // Calculate assigned users locally
      final assigned = <int>[];
      for (var u in users) {
        if (u['assigned_screen_id'] == widget.screenId) {
          assigned.add(u['id']);
        }
      }

      if (mounted) {
        setState(() {
          _screenData = data;
          _slides = data['slides'] ?? [];
          _currentDeviceType = data['device_type'] ?? 'tablet';
          _currentOrientation = data['orientation'] ?? 'landscape';
          _slideshowEnabled = (data['slideshow_enabled'] ?? 1) == 1;
          _slideDuration = data['slide_duration'] ?? 5;
          _selectedSlideId = data['selected_slide_id'];
          _shopUsers = users;
          _assignedUserIds = assigned;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _saveUserAssignments() async {
    try {
      await context.read<ApiService>().assignUsersToScreen(widget.screenId, _assignedUserIds);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Utilisateurs assignés avec succès !")));
        _loadDetails(); // Reload to refresh state
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Erreur assignation: $e")));
    }
  }

  void _toggleUserAssignment(int userId, bool? isChecked) {
    setState(() {
      if (isChecked == true) {
        if (!_assignedUserIds.contains(userId)) {
          _assignedUserIds.add(userId);
        }
      } else {
        _assignedUserIds.remove(userId);
      }
    });
  }

  // ... (UpdateSettings, GetDisplayUrl, DeleteScreen methods remain same) ...

  Future<void> _updateSettings(String key, String value) async {
    try {
      if (key == 'device_type') _currentDeviceType = value;
      if (key == 'orientation') _currentOrientation = value;
      
      await context.read<ApiService>().updateScreen(widget.screenId, 
        deviceType: key == 'device_type' ? value : null,
        orientation: key == 'orientation' ? value : null
      );
      
      setState(() {});
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Paramètres mis à jour !"), duration: Duration(seconds: 1)));
    } catch (e) {
      _loadDetails(); // Revert on error
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Erreur update: $e")));
    }
  }
  
  String _getDisplayUrl() {
    if (_screenData == null) return '';
    final token = _screenData!['token'];
    return "${ApiConfig.siteUrl}/display.php?token=$token";
  }

  Future<void> _deleteScreen() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text("Supprimer l'écran ?"),
        content: const Text("Cette action est irréversible."),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text("Annuler")),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text("Supprimer"),
          )
        ],
      ),
    );

    if (confirm == true) {
      try {
        await context.read<ApiService>().deleteScreen(widget.screenId);
        if (mounted) Navigator.pop(context);
      } catch (e) {
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Erreur: $e")));
      }
    }
  }

  // ... (AddSlideImage/Text, DeleteSlide methods remain same) ...

  Future<void> _addSlideImage() async {
    try {
      final ImagePicker picker = ImagePicker();
      final XFile? image = await picker.pickImage(source: ImageSource.gallery);
      if (image != null) {
        await context.read<ApiService>().addSlide(
          widget.screenId, 
          'IMAGE', 
          filePath: image.path
        );
        _loadDetails();
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Erreur upload: $e")));
    }
  }

  Future<void> _addSlideText() async {
    final textCtrl = TextEditingController();
    Color currentColor = Colors.white;
    
    await showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text("Nouveau Slide Texte"),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: textCtrl,
              decoration: const InputDecoration(labelText: "Message à afficher"),
              maxLines: 3,
            ),
            const SizedBox(height: 16),
            const Text("Couleur du texte:"),
            const SizedBox(height: 8),
            BlockPicker(
              pickerColor: currentColor,
              onColorChanged: (c) => currentColor = c,
            ),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text("Annuler")),
          ElevatedButton(
            onPressed: () async {
              if (textCtrl.text.isEmpty) return;
              Navigator.pop(ctx);
              try {
                String hex = '#${currentColor.value.toRadixString(16).substring(2).toUpperCase()}';
                await context.read<ApiService>().addSlide(
                  widget.screenId, 
                  'TEXT', 
                  text: textCtrl.text,
                  color: hex
                );
                _loadDetails();
              } catch (e) {
                if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Erreur: $e")));
              }
            },
            child: const Text("Ajouter"),
          )
        ],
      ),
    );
  }

  Future<void> _deleteSlide(int id) async {
    try {
      await context.read<ApiService>().deleteSlide(id);
      _loadDetails();
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Erreur: $e")));
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) return const Scaffold(body: Center(child: CircularProgressIndicator()));
    if (_screenData == null) return const Scaffold(body: Center(child: Text("Erreur de chargement")));

    final url = _getDisplayUrl();
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return AppShell(
      currentRoute: '/settings/screens',
      content: Scaffold(
        appBar: AppBar(
          title: Text(widget.screenName),
          backgroundColor: Colors.transparent,
          elevation: 0,
          actions: [
            IconButton(
              icon: const Icon(CupertinoIcons.trash, color: Colors.red),
              onPressed: _deleteScreen,
            ),
            const SizedBox(width: 16),
          ],
        ),
        body: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Left Panel: Info, QR, Settings
            Container(
              width: 300,
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(border: Border(right: BorderSide(color: MacOSTheme.divider))),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text("CONFIGURATION", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Colors.grey)),
                  const SizedBox(height: 16),
                  
                  // --- Device Type ---
                  DropdownButtonFormField<String>(
                    value: _currentDeviceType,
                    decoration: const InputDecoration(labelText: "Type d'appareil", border: OutlineInputBorder()),
                    dropdownColor: isDark ? const Color(0xFF2C2C2C) : Colors.white,
                    items: const [
                      DropdownMenuItem(value: 'tablet', child: Row(children: [Icon(Icons.tablet_mac), SizedBox(width: 8), Text("Tablette")])),
                      DropdownMenuItem(value: 'smartphone', child: Row(children: [Icon(Icons.phone_iphone), SizedBox(width: 8), Text("Smartphone")])),
                      DropdownMenuItem(value: 'monitor', child: Row(children: [Icon(Icons.desktop_mac), SizedBox(width: 8), Text("Moniteur")])),
                    ],
                    onChanged: (v) => _updateSettings('device_type', v!),
                  ),
                  
                  const SizedBox(height: 16),
                  
                  // --- Orientation ---
                  DropdownButtonFormField<String>(
                    value: _currentOrientation,
                    decoration: const InputDecoration(labelText: "Orientation", border: OutlineInputBorder()),
                    dropdownColor: isDark ? const Color(0xFF2C2C2C) : Colors.white,
                    items: const [
                      DropdownMenuItem(value: 'landscape', child: Row(children: [Icon(Icons.landscape), SizedBox(width: 8), Text("Horizontale")])),
                      DropdownMenuItem(value: 'portrait', child: Row(children: [Icon(Icons.portrait), SizedBox(width: 8), Text("Verticale")])),
                    ],
                    onChanged: (v) => _updateSettings('orientation', v!),
                  ),
                  
                  const SizedBox(height: 30),
                  const Divider(),
                  const SizedBox(height: 20),
                  
                  const Text("ADRESSE DE L'ÉCRAN", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Colors.grey)),
                  const SizedBox(height: 10),
                  Center(
                    child: Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12)),
                      child: QrImageView(
                        data: url,
                        version: QrVersions.auto,
                        size: 180.0,
                        backgroundColor: Colors.white,
                      ),
                    ),
                  ),
                  const SizedBox(height: 10),
                  Center(
                    child: SelectableText(
                      url, 
                      style: const TextStyle(fontSize: 10, color: Colors.blue),
                      textAlign: TextAlign.center,
                    ),
                  ),
                  const SizedBox(height: 10),
                  Center(
                    child: Text("Status: ${_screenData!['status']}", style: TextStyle(color: _screenData!['status'] == 'IDLE' ? Colors.green : Colors.orange, fontWeight: FontWeight.bold))
                  ),
                ],
              ),
            ),
            
            // Right Panel: Slides + Users
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // --- Slideshow Toggle ---
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                const Text("Activer le diaporama", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                                Switch(
                                  value: _slideshowEnabled,
                                  onChanged: (val) async {
                                    setState(() => _slideshowEnabled = val);
                                    try {
                                      await context.read<ApiService>().updateScreen(
                                        widget.screenId,
                                        slideshowEnabled: val,
                                      );
                                    } catch (e) {
                                      _loadDetails();
                                    }
                                  },
                                ),
                              ],
                            ),
                            if (_slideshowEnabled) ...[
                              const SizedBox(height: 16),
                              const Text("Durée entre les slides (secondes)"),
                              const SizedBox(height: 8),
                              Row(
                                children: [
                                  Expanded(
                                    child: Slider(
                                      value: _slideDuration.toDouble(),
                                      min: 2,
                                      max: 30,
                                      divisions: 14,
                                      label: "${_slideDuration}s",
                                      onChanged: (val) => setState(() => _slideDuration = val.round()),
                                      onChangeEnd: (val) async {
                                        try {
                                          await context.read<ApiService>().updateScreen(
                                            widget.screenId,
                                            slideDuration: val.round(),
                                          );
                                        } catch (e) {
                                          _loadDetails();
                                        }
                                      },
                                    ),
                                  ),
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                                    decoration: BoxDecoration(
                                      color: Theme.of(context).primaryColor.withOpacity(0.1),
                                      borderRadius: BorderRadius.circular(8),
                                    ),
                                    child: Text("${_slideDuration}s", style: const TextStyle(fontWeight: FontWeight.bold)),
                                  ),
                                ],
                              ),
                            ],
                            if (!_slideshowEnabled && _slides.isNotEmpty) ...[
                              const SizedBox(height: 16),
                              const Text("Image à afficher :", style: TextStyle(fontWeight: FontWeight.w500)),
                              const SizedBox(height: 8),
                              Wrap(
                                spacing: 10,
                                runSpacing: 10,
                                children: _slides.map<Widget>((slide) {
                                  final isImage = slide['type'] == 'IMAGE';
                                  final url = slide['content_url'];
                                  final fullUrl = url.startsWith('http') ? url : "${ApiConfig.siteUrl}$url";
                                  final isSelected = _selectedSlideId == slide['id'];
                                  return GestureDetector(
                                    onTap: () async {
                                      setState(() => _selectedSlideId = slide['id']);
                                      try {
                                        await context.read<ApiService>().updateScreen(
                                          widget.screenId,
                                          selectedSlideId: slide['id'],
                                        );
                                      } catch (e) {
                                        _loadDetails();
                                      }
                                    },
                                    child: Container(
                                      width: 100,
                                      height: 60,
                                      decoration: BoxDecoration(
                                        border: Border.all(
                                          color: isSelected ? Colors.blue : Colors.grey.shade300,
                                          width: isSelected ? 3 : 1,
                                        ),
                                        borderRadius: BorderRadius.circular(8),
                                      ),
                                      child: ClipRRect(
                                        borderRadius: BorderRadius.circular(6),
                                        child: isImage
                                            ? Image.network(fullUrl, fit: BoxFit.cover)
                                            : const Center(child: Icon(Icons.text_fields)),
                                      ),
                                    ),
                                  );
                                }).toList(),
                              ),
                            ],
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 20),
                    
                    // --- Slides Section ---
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(_slideshowEnabled ? "Images du diaporama" : "Bibliothèque d'images", style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                        ElevatedButton.icon(
                          onPressed: _addSlideImage,
                          icon: const Icon(Icons.add_photo_alternate),
                          label: const Text("Ajouter Image"),
                          style: ElevatedButton.styleFrom(
                            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 20),
                    SizedBox(
                      height: 300,
                      child: _slides.isEmpty 
                        ? const Center(child: Text("Aucun slide configuré.\nL'écran affichera 'Bienvenue' par défaut.", textAlign: TextAlign.center, style: TextStyle(color: Colors.grey)))
                        : GridView.builder(
                            physics: const ScrollPhysics(),
                            gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(
                              maxCrossAxisExtent: 300,
                              childAspectRatio: 16/9,
                              crossAxisSpacing: 20,
                              mainAxisSpacing: 20,
                            ),
                            itemCount: _slides.length,
                            itemBuilder: (ctx, i) {
                               final slide = _slides[i];
                               final isImage = slide['type'] == 'IMAGE';
                               final url = slide['content_url'];
                               final fullUrl = url.startsWith('http') ? url : "${ApiConfig.siteUrl}$url";
                               
                               return Card(
                                 clipBehavior: Clip.antiAlias,
                                 child: Stack(
                                   children: [
                                     Positioned.fill(
                                       child: isImage 
                                         ? Image.network(fullUrl, fit: BoxFit.cover, errorBuilder: (_,__,___) => const Center(child: Icon(Icons.broken_image)))
                                         : Center(child: Text(slide['content_url'] ?? "Texte", textAlign: TextAlign.center, style: const TextStyle(fontWeight: FontWeight.bold))),
                                     ),
                                     Positioned(
                                       top: 5, right: 5,
                                       child: IconButton(
                                         icon: const Icon(Icons.delete, color: Colors.red),
                                         onPressed: () => _deleteSlide(slide['id']),
                                       ),
                                     ),
                                     Positioned(
                                       bottom: 5, left: 5, 
                                       child: Chip(
                                         label: Text(slide['type'], style: const TextStyle(fontSize: 10)),
                                         visualDensity: VisualDensity.compact,
                                       )
                                     )
                                   ],
                                 ),
                               );
                            },
                          ),
                    ),

                    const SizedBox(height: 40),
                    const Divider(),
                    const SizedBox(height: 20),

                    // --- Users Assignment Section ---
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text("Utilisateurs Assignés", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                        ElevatedButton.icon(
                          onPressed: _saveUserAssignments,
                          icon: const Icon(Icons.save),
                          label: const Text("Enregistrer les Utilisateurs"),
                          style: ElevatedButton.styleFrom(
                             backgroundColor: Colors.blueAccent,
                             foregroundColor: Colors.white,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 10),
                    const Text("Sélectionnez les utilisateurs qui utiliseront cet écran pour les signatures client. (Un utilisateur ne peut être assigné qu'à un seul écran à la fois).", style: TextStyle(color: Colors.grey)),
                    const SizedBox(height: 20),
                    
                    Card(
                      child: ListView.separated(
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        itemCount: _shopUsers.length,
                        separatorBuilder: (ctx, i) => const Divider(height: 1),
                        itemBuilder: (ctx, i) {
                          final user = _shopUsers[i];
                          final isAssignedHere = _assignedUserIds.contains(user['id']);
                          final assignedElsewhere = user['assigned_screen_id'] != null && user['assigned_screen_id'] != widget.screenId;
                          
                          return CheckboxListTile(
                            title: Text(user['full_name'] ?? 'Utilisateur inconnu', style: const TextStyle(fontWeight: FontWeight.bold)),
                            subtitle: assignedElsewhere 
                              ? Text("Actuellement assigné à l'écran #${user['assigned_screen_id']}", style: const TextStyle(color: Colors.orange))
                              : Text(user['role'] ?? 'Technicien'),
                            value: isAssignedHere,
                            onChanged: (val) => _toggleUserAssignment(user['id'], val),
                            secondary: CircleAvatar(child: Text((user['full_name']?[0] ?? '?').toUpperCase())),
                            checkColor: Colors.white,
                            activeColor: Colors.blue,
                          );
                        },
                      ),
                    ),
                    const SizedBox(height: 50), // Bottom padding
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
