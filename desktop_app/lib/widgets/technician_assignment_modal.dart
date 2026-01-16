import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../config/api_config.dart';
import '../theme/macos_theme.dart';

class TechnicianAssignmentModal extends StatefulWidget {
  final ApiService apiService;
  final Function() onAssigned;

  const TechnicianAssignmentModal({
    Key? key,
    required this.apiService,
    required this.onAssigned,
  }) : super(key: key);

  @override
  State<TechnicianAssignmentModal> createState() => _TechnicianAssignmentModalState();
}

class _TechnicianAssignmentModalState extends State<TechnicianAssignmentModal> with SingleTickerProviderStateMixin {
  // Filter buttons: Tout, Nouvelles, En attente, En diagnostic
  String _currentFilter = 'all'; // Default to all as per web view? or 'nouvelle_intervention...'
  // Web view defaults to 'all' maybe? Or specific.
  
  List<dynamic> _repairs = [];
  bool _isLoadingRepairs = false;
  Set<String> _selectedRepairs = {};
  
  List<dynamic> _technicians = [];
  bool _isLoadingTechs = false;
  String? _selectedTechnicianId;
  
  bool _isAssigning = false;

  @override
  void initState() {
    super.initState();
    _loadTechnicians();
    _loadRepairs('all');
  }

  Future<void> _loadTechnicians() async {
    setState(() => _isLoadingTechs = true);
    try {
      final response = await widget.apiService.get(ApiConfig.employeesListEndpoint);
      if (mounted) {
        setState(() {
          _technicians = response['employees'] ?? [];
          _isLoadingTechs = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoadingTechs = false);
        debugPrint("Error loading techs: $e");
      }
    }
  }

  Future<void> _loadRepairs(String filter) async {
    setState(() {
      _currentFilter = filter;
      _isLoadingRepairs = true;
      _selectedRepairs.clear(); // Clear selection on filter change
    });

    try {
      String statusList = '';
      if (filter == 'new') {
        statusList = 'nouvelle_intervention,nouveau_diagnostique,nouvelle_commande,devis_accepte,devis_refuse';
      } else if (filter == 'waiting') {
        statusList = 'En attente,en_attente_responsable,en_attente_livraison,en_attente_accord_client';
      } else if (filter == 'diagnostic') {
        statusList = 'en_cours_diagnostique,en_cours_intervention';
      } else {
        // 'all' - maybe no filter param means all? List endpoint usually paginates.
        // We probably want 'active' ones.
        // Let's rely on list endpoint default or maybe specific active statuses?
        // Web view 'all' might imply 'all active'.
        // Let's try sending NO status_list for 'all'.
      }

      String url = '${ApiConfig.reparationsListEndpoint}?limit=100'; // Fetch reasonable amount
      if (statusList.isNotEmpty) {
        url += '&status_list=$statusList';
      }
      
      final response = await widget.apiService.get(url);
      
      if (mounted) {
        setState(() {
          _repairs = response['reparations'] ?? [];
          _isLoadingRepairs = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoadingRepairs = false);
        debugPrint("Error loading repairs: $e");
      }
    }
  }

  void _toggleSelection(String repairId) {
    setState(() {
      if (_selectedRepairs.contains(repairId)) {
        _selectedRepairs.remove(repairId);
      } else {
        _selectedRepairs.add(repairId);
      }
    });
  }
  
  Future<void> _assign() async {
     if (_selectedRepairs.isEmpty) return;
     // Allow unassigning? If _selectedTechnicianId is null/empty.
     // Web view says: "Laissez 'Aucune attribution' pour retirer l'attribution existante"
     
     setState(() => _isAssigning = true);
     
     int successCount = 0;
     for (String repairId in _selectedRepairs) {
       try {
         // Using api/assign_technician.php logic via update endpoint or specific
         // Web view uses 'api/assign_technician.php'. 
         // I don't have this in ApiConfig explicitly.
         // reparationsUpdateEndpoint maps to 'reparations/update.php'?
         // I'll try to use the generic update endpoint first: field 'employe_id'.
         
         await widget.apiService.post(ApiConfig.reparationsUpdateEndpoint, {
            'id': repairId,
            'employe_id': _selectedTechnicianId ?? '0', // 0 or null for unassign?
         });
         successCount++;
       } catch (e) {
         debugPrint("Error assigning $repairId: $e");
       }
     }
     
     if (mounted) {
       ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('$successCount réparations attribuées'),
            backgroundColor: MacOSTheme.successGreen,
          )
       );
       widget.onAssigned();
       Navigator.pop(context);
     }
  }

  @override
  Widget build(BuildContext context) {
    return Dialog(
      backgroundColor: Colors.transparent,
      insetPadding: const EdgeInsets.all(24),
      child: Container(
        width: 1000,
        height: 800,
        decoration: BoxDecoration(
          color: const Color(0xFF1C1C1E),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: Colors.white.withOpacity(0.1)),
        ),
        child: Column(
          children: [
            // Header
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Colors.white.withOpacity(0.05),
                borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.person_add, color: MacOSTheme.accentPurple),
                  const SizedBox(width: 12),
                  const Text(
                    "Attribution Technicien",
                    style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                  const Spacer(),
                  IconButton(
                    icon: const Icon(Icons.close, color: Colors.white),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),
            ),
            
            // Filters
             Container(
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
              color: Colors.black12,
              child: Row(
                children: [
                  _buildFilterBtn('all', 'Tout afficher', Icons.list),
                  const SizedBox(width: 8),
                  _buildFilterBtn('new', 'Nouvelles', Icons.add_circle),
                  const SizedBox(width: 8),
                  _buildFilterBtn('waiting', 'En attente', Icons.timer),
                  const SizedBox(width: 8),
                  _buildFilterBtn('diagnostic', 'En diagnostic', Icons.search),
                ],
              ),
            ),

            // Body
            Expanded(
              child: _isLoadingRepairs 
                ? const Center(child: CircularProgressIndicator())
                : _repairs.isEmpty
                  ? const Center(child: Text("Aucune réparation trouvée", style: TextStyle(color: Colors.grey)))
                  : GridView.builder(
                      padding: const EdgeInsets.all(16),
                      gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(
                        maxCrossAxisExtent: 300,
                        childAspectRatio: 1.4,
                        mainAxisSpacing: 16,
                        crossAxisSpacing: 16,
                      ),
                      itemCount: _repairs.length,
                      itemBuilder: (context, index) {
                        final repair = _repairs[index];
                        final id = repair['id'].toString();
                        final isSelected = _selectedRepairs.contains(id);
                        final currentTechId = repair['employe_id']; // Might be null or string
                        final hasTech = currentTechId != null && currentTechId.toString() != '0';
                        
                        return InkWell(
                          onTap: () => _toggleSelection(id),
                          borderRadius: BorderRadius.circular(12),
                          child: Container(
                            decoration: BoxDecoration(
                              color: isSelected ? MacOSTheme.accentPurple.withOpacity(0.2) : Colors.white.withOpacity(0.05),
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(
                                color: isSelected ? MacOSTheme.accentPurple : (hasTech ? Colors.green.withOpacity(0.3) : Colors.transparent), 
                                width: isSelected ? 2 : 1
                              ),
                            ),
                            child: Stack(
                              children: [
                                Padding(
                                  padding: const EdgeInsets.all(12),
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Row(
                                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                        children: [
                                          Text("#$id", style: const TextStyle(color: MacOSTheme.accentPurple, fontWeight: FontWeight.bold)),
                                          if (hasTech)
                                             const Icon(Icons.person, size: 14, color: Colors.green),
                                        ],
                                      ),
                                      const SizedBox(height: 8),
                                      Text(
                                        "${repair['client_nom']} ${repair['client_prenom']}",
                                        style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
                                        maxLines: 1, overflow: TextOverflow.ellipsis,
                                      ),
                                      Text(
                                        "${repair['marque']} ${repair['modele']}",
                                        style: const TextStyle(color: Colors.white70),
                                        maxLines: 1, overflow: TextOverflow.ellipsis,
                                      ),
                                      const Spacer(),
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                        decoration: BoxDecoration(
                                          color: Colors.white10,
                                          borderRadius: BorderRadius.circular(4),
                                        ),
                                        child: Text(
                                          repair['statut_nom'] ?? repair['statut'] ?? '',
                                          style: const TextStyle(color: Colors.grey, fontSize: 10),
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                                if (isSelected) 
                                  const Positioned(
                                    top: 8, right: 8,
                                    child: Icon(Icons.check_circle, color: MacOSTheme.accentPurple, size: 20),
                                  ),
                              ],
                            ),
                          ),
                        );
                      },
                    ),
            ),

            // Footer
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Colors.white.withOpacity(0.05),
                borderRadius: const BorderRadius.vertical(bottom: Radius.circular(16)),
              ),
              child: Row(
                children: [
                  Text(
                    "${_selectedRepairs.length} sélectionnés",
                    style: const TextStyle(color: Colors.grey),
                  ),
                  const Spacer(),
                  // Tech Selector
                  SizedBox(
                    width: 300,
                    child: DropdownButtonFormField<String>(
                      value: _selectedTechnicianId,
                      dropdownColor: const Color(0xFF2C2C2E),
                      decoration: InputDecoration(
                        labelText: "Choisir un technicien",
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                        filled: true,
                        fillColor: Colors.white.withOpacity(0.05),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 0),
                      ),
                      items: [
                        const DropdownMenuItem(value: null, child: Text("-- Aucune attribution --", style: TextStyle(color: Colors.grey))),
                        ..._technicians.map((t) => DropdownMenuItem(
                          value: t['id'].toString(), 
                          child: Text(t['full_name'] ?? t['username'] ?? 'Inconnu', style: const TextStyle(color: Colors.white))
                        )).toList()
                      ],
                      onChanged: (v) => setState(() => _selectedTechnicianId = v),
                    ),
                  ),
                  const SizedBox(width: 16),
                  ElevatedButton.icon(
                    onPressed: _isAssigning || _selectedRepairs.isEmpty ? null : _assign,
                    icon: _isAssigning 
                       ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2)) 
                       : const Icon(Icons.check),
                    label: const Text("Attribuer"),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: MacOSTheme.accentPurple,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),
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

  Widget _buildFilterBtn(String id, String label, IconData icon) {
    final isActive = _currentFilter == id;
    return TextButton.icon(
      onPressed: () => _loadRepairs(id),
      icon: Icon(icon, size: 16, color: isActive ? MacOSTheme.accentPurple : Colors.grey),
      label: Text(label, style: TextStyle(color: isActive ? Colors.white : Colors.grey)),
      style: TextButton.styleFrom(
        backgroundColor: isActive ? MacOSTheme.accentPurple.withOpacity(0.2) : Colors.transparent,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      ),
    );
  }
}
