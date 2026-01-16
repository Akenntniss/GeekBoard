import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:geekboard_desktop/config/api_config.dart';
import 'package:intl/intl.dart';

class HistoryDialog extends StatefulWidget {
  final dynamic partner;

  const HistoryDialog({super.key, required this.partner});

  @override
  State<HistoryDialog> createState() => _HistoryDialogState();
}

class _HistoryDialogState extends State<HistoryDialog> {
  bool _isLoading = true;
  List<dynamic> _transactions = [];
  double _currentBalance = 0.0;

  @override
  void initState() {
    super.initState();
    _fetchHistory();
  }

  Future<void> _fetchHistory() async {
    setState(() => _isLoading = true);
    try {
      final url = Uri.parse('${ApiConfig.baseUrl}${ApiConfig.partnersTransactionsEndpoint}?partner_id=${widget.partner['id']}');
      final response = await http.get(url);

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _transactions = data['transactions'];
            _currentBalance = (data['solde'] as num).toDouble();
            _isLoading = false;
          });
        }
      } else {
        throw Exception('Failed to load history');
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

  Future<void> _validateTransaction(int pendingId, String action) async {
    // Confirmation dialog
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: const Color(0xFF1a1a2e),
        title: Text(action == 'approve' ? 'Accepter ?' : 'Refuser ?', style: const TextStyle(color: Colors.white)),
        content: Text(
          action == 'approve' 
              ? 'Voulez-vous accepter cette transaction ?' 
              : 'Voulez-vous refuser cette transaction ?',
          style: const TextStyle(color: Colors.white70),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Non')),
          TextButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Oui')),
        ],
      ),
    );

    if (confirmed != true) return;

    try {
      final url = Uri.parse('${ApiConfig.baseUrl}${ApiConfig.partnersValidateTransactionEndpoint}');
      final response = await http.post(
        url,
        headers: {'Content-Type': 'application/json'},
        body: json.encode({
          'pending_id': pendingId,
          'action': action,
          'reason': action == 'reject' ? 'Rejeté par admin' : '',
        }),
      );

      final data = json.decode(response.body);
      if (response.statusCode == 200 && data['success']) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(action == 'approve' ? 'Transaction validée' : 'Transaction rejetée'),
              backgroundColor: action == 'approve' ? Colors.green : Colors.orange,
            ),
          );
          _fetchHistory(); // Refresh
        }
      } else {
        throw Exception(data['message'] ?? 'Erreur inconnue');
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Dialog(
      backgroundColor: const Color(0xFF1a1a2e),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Container(
        width: 1000,
        height: 800,
        padding: const EdgeInsets.all(24),
        child: Column(
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Historique des Transactions',
                      style: TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold),
                    ),
                    Text(
                      widget.partner['nom'],
                      style: TextStyle(color: Colors.white.withOpacity(0.7), fontSize: 14),
                    ),
                  ],
                ),
                IconButton(
                  icon: const Icon(Icons.close, color: Colors.white),
                  onPressed: () => Navigator.pop(context),
                ),
              ],
            ),
            const SizedBox(height: 24),
            
            // Stats Row
            Row(
              children: [
                Expanded(child: _buildStatCard('Solde Actuel', '${_currentBalance.toStringAsFixed(2)} €', _currentBalance >= 0 ? Colors.green : Colors.red)),
                const SizedBox(width: 16),
                Expanded(child: _buildStatCard('Transactions', '${_transactions.length}', Colors.blue)),
              ],
            ),
            
            const SizedBox(height: 24),
            
            // Table
            Expanded(
              child: _isLoading 
                  ? const Center(child: CircularProgressIndicator())
                  : _transactions.isEmpty
                      ? Center(child: Text('Aucune transaction', style: TextStyle(color: Colors.grey)))
                      : SingleChildScrollView(
                          child: Table(
                            columnWidths: const {
                              0: FlexColumnWidth(1), // Date
                              1: FlexColumnWidth(1), // Type
                              2: FlexColumnWidth(1), // Montant
                              3: FlexColumnWidth(2), // Description
                              4: FlexColumnWidth(1), // Statut
                              5: FlexColumnWidth(1), // Actions
                            },
                            defaultVerticalAlignment: TableCellVerticalAlignment.middle,
                            border: TableBorder(
                              horizontalInside: BorderSide(color: Colors.white.withOpacity(0.1)),
                              bottom: BorderSide(color: Colors.white.withOpacity(0.1)),
                            ),
                            children: [
                              _buildHeaderRow(),
                              ..._transactions.map((t) => _buildTransactionRow(t)),
                            ],
                          ),
                        ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatCard(String title, String value, Color color) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.05),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.white.withOpacity(0.1)),
      ),
      child: Column(
        children: [
          Text(title, style: TextStyle(color: Colors.white.withOpacity(0.7), fontSize: 13)),
          const SizedBox(height: 8),
          Text(
            value,
            style: TextStyle(color: color, fontSize: 24, fontWeight: FontWeight.bold),
          ),
        ],
      ),
    );
  }

  TableRow _buildHeaderRow() {
    return TableRow(
      decoration: BoxDecoration(color: Colors.white.withOpacity(0.05)),
      children: const [
        Padding(padding: EdgeInsets.all(12), child: Text('Date', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold))),
        Padding(padding: EdgeInsets.all(12), child: Text('Type', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold))),
        Padding(padding: EdgeInsets.all(12), child: Text('Montant', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold))),
        Padding(padding: EdgeInsets.all(12), child: Text('Description', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold))),
        Padding(padding: EdgeInsets.all(12), child: Text('Statut', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold))),
        Padding(padding: EdgeInsets.all(12), child: Text('Actions', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold))),
      ],
    );
  }

  TableRow _buildTransactionRow(dynamic t) {
    final isCredit = t['type'] == 'credit';
    final amount = (t['montant'] as num).toDouble();
    final status = t['status']; // approved, pending, rejected
    final pendingId = t['pending_id'];
    
    Color statusColor = Colors.grey;
    if (status == 'approved') statusColor = Colors.green;
    if (status == 'pending') statusColor = Colors.orange;
    if (status == 'rejected') statusColor = Colors.red;
    
    String statusText = status == 'approved' ? 'Validée' : (status == 'pending' ? 'En attente' : 'Rejetée');

    return TableRow(
      children: [
        Padding(
          padding: const EdgeInsets.all(12),
          child: Text(
            t['date_transaction'] != null ? DateFormat('dd/MM/yyyy').format(DateTime.parse(t['date_transaction'])) : 'N/A',
            style: const TextStyle(color: Colors.white70),
          ),
        ),
        Padding(
          padding: const EdgeInsets.all(12),
          child: Row(
            children: [
              Icon(isCredit ? Icons.arrow_upward : Icons.arrow_downward, color: isCredit ? Colors.green : Colors.red, size: 16),
              const SizedBox(width: 4),
              Text(
                isCredit ? 'Crédit' : 'Débit',
                style: TextStyle(color: isCredit ? Colors.green : Colors.red),
              ),
            ],
          ),
        ),
        Padding(
          padding: const EdgeInsets.all(12),
          child: Text(
            '${amount.toStringAsFixed(2)} €',
            style: TextStyle(color: isCredit ? Colors.green : Colors.red, fontWeight: FontWeight.bold),
          ),
        ),
        Padding(
          padding: const EdgeInsets.all(12),
          child: Text(
            t['description'] ?? '',
            style: const TextStyle(color: Colors.white70),
          ),
        ),
        Padding(
          padding: const EdgeInsets.all(12),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
            decoration: BoxDecoration(
              color: statusColor.withOpacity(0.2),
              borderRadius: BorderRadius.circular(8),
              border: Border.all(color: statusColor),
            ),
            child: Text(
              statusText,
              style: TextStyle(color: statusColor, fontSize: 12),
              textAlign: TextAlign.center,
            ),
          ),
        ),
        Padding(
          padding: const EdgeInsets.all(12),
          child: (status == 'pending' && pendingId != null)
              ? Row(
                  children: [
                    IconButton(
                      icon: const Icon(Icons.check, color: Colors.green),
                      tooltip: 'Accepter',
                      onPressed: () => _validateTransaction(pendingId, 'approve'),
                    ),
                    IconButton(
                      icon: const Icon(Icons.close, color: Colors.red),
                      tooltip: 'Refuser',
                      onPressed: () => _validateTransaction(pendingId, 'reject'),
                    ),
                  ],
                )
              : const SizedBox(),
        ),
      ],
    );
  }
}
