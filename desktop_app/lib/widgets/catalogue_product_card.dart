import 'package:flutter/material.dart';

class CatalogueProductCard extends StatelessWidget {
  final Map<String, dynamic> product;
  final VoidCallback onAddToCart;

  const CatalogueProductCard({
    super.key,
    required this.product,
    required this.onAddToCart,
  });

  @override
  Widget build(BuildContext context) {
    final stock = product['stock'] ?? '';
    final isStock = stock.contains('En stock');
    final isRupture = stock.contains('Rupture');
    
    // Status Badge
    Color statusColor = Colors.grey;
    if (isStock) statusColor = const Color(0xFF10B981); // Emerald
    if (isRupture) statusColor = const Color(0xFFEF4444); // Red
    
    final price = double.tryParse(product['price'].toString()) ?? 0.0;

    return Container(
      decoration: BoxDecoration(
        color: const Color(0xFF1E293B),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.white.withOpacity(0.05)),
        boxShadow: [
           BoxShadow(color: Colors.black.withOpacity(0.2), blurRadius: 6, offset: const Offset(0, 4)),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header / Image Placeholder
          Container(
            height: 120,
            width: double.infinity,
            decoration: const BoxDecoration(
              color: Color(0xFF0F172A),
              borderRadius: BorderRadius.only(topLeft: Radius.circular(16), topRight: Radius.circular(16)),
            ),
            child: Center(
              child: Icon(Icons.devices_other, size: 40, color: Colors.white.withOpacity(0.1)),
            ),
          ),
          
          Expanded(
            child: Padding(
              padding: const EdgeInsets.all(12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                   // Brand Badge
                   if (product['brand'] != null)
                     Container(
                       margin: const EdgeInsets.only(bottom: 8),
                       padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                       decoration: BoxDecoration(
                         borderRadius: BorderRadius.circular(4),
                         color: Colors.white.withOpacity(0.05),
                       ),
                       child: Text(
                         (product['brand'] ?? '').toString().toUpperCase(),
                         style: const TextStyle(color: Colors.grey, fontSize: 10, fontWeight: FontWeight.bold),
                       ),
                     ),
                     
                   Text(
                     product['name'] ?? 'Produit',
                     style: const TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.w500),
                     maxLines: 2,
                     overflow: TextOverflow.ellipsis,
                   ),
                   const Spacer(),
                   
                   // Info Row
                   Row(
                     mainAxisAlignment: MainAxisAlignment.spaceBetween,
                     children: [
                        Text(
                          '${price.toStringAsFixed(2)} €',
                          style: const TextStyle(color: Color(0xFF22D3EE), fontSize: 16, fontWeight: FontWeight.bold),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                          decoration: BoxDecoration(
                            color: statusColor.withOpacity(0.1),
                            borderRadius: BorderRadius.circular(4),
                            border: Border.all(color: statusColor.withOpacity(0.5)),
                          ),
                          child: Text(
                            isStock ? 'STOCK' : (isRupture ? 'RUPTURE' : stock),
                            style: TextStyle(color: statusColor, fontSize: 10, fontWeight: FontWeight.bold),
                          ),
                        ),
                     ],
                   ),
                   const SizedBox(height: 12),
                   SizedBox(
                     width: double.infinity,
                     child: ElevatedButton(
                       onPressed: onAddToCart,
                       style: ElevatedButton.styleFrom(
                         backgroundColor: const Color(0xFF3B82F6),
                         foregroundColor: Colors.white,
                         shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                         padding: const EdgeInsets.symmetric(vertical: 12),
                       ),
                       child: const Text("Ajouter au panier"),
                     ),
                   ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
