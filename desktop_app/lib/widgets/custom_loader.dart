
import 'package:flutter/material.dart';
import 'dart:math';

class CustomLoader extends StatefulWidget {
  final double size;
  final Color color;

  const CustomLoader({
    Key? key,
    this.size = 44.8,
    this.color = const Color(0xFF554cb5),
  }) : super(key: key);

  @override
  State<CustomLoader> createState() => _CustomLoaderState();
}

class _CustomLoaderState extends State<CustomLoader> with SingleTickerProviderStateMixin {
  late AnimationController _controller;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1500),
    )..repeat();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _controller,
      builder: (context, child) {
        return CustomPaint(
          size: Size(widget.size, widget.size),
          painter: _LoaderPainter(
            progress: _controller.value,
            color: widget.color,
          ),
        );
      },
    );
  }
}

class _LoaderPainter extends CustomPainter {
  final double progress;
  final Color color;

  _LoaderPainter({required this.progress, required this.color});

  @override
  void paint(Canvas canvas, Size size) {
    final center = Offset(size.width / 2, size.height / 2);
    // Base radius for the dots (approx 10.08px relative to 44.8px total -> ~22.5%)
    final dotRadius = size.width * 0.225; 
    
    // Animation phases matching the CSS keyframes:
    // 0% - 33%: Inset -11.2px (expand out), Rotate 0
    // 33% - 66%: Inset -11.2px (stay out), Rotate 0 -> 90
    // 66% - 100%: Inset 0 (contract in), Rotate 90
    
    double expansion = 0.0;
    double rotation = 0.0;
    
    if (progress <= 0.33) {
      // Phase 1: Expand
      // 0 -> 1
      final t = progress / 0.33;
      // CSS inset -11.2px means expanding outwards. 11.2px is 25% of 44.8px.
      expansion = t * (size.width * 0.25);
      rotation = 0.0;
    } else if (progress <= 0.66) {
      // Phase 2: Rotate
      final t = (progress - 0.33) / 0.33;
      expansion = size.width * 0.25;
      rotation = t * (pi / 2); // 0 to 90 degrees
    } else {
      // Phase 3: Contract
      final t = (progress - 0.66) / 0.34;
      expansion = (1 - t) * (size.width * 0.25);
      rotation = pi / 2;
    }

    canvas.save();
    canvas.translate(center.dx, center.dy);
    canvas.rotate(rotation);

    final Paint paint = Paint()
      ..shader = RadialGradient(
        colors: [color, Colors.transparent],
        stops: const [0.94, 1.0], // Hard edge at 94% like CSS
      ).createShader(Rect.fromCircle(center: Offset.zero, radius: dotRadius));

    // Draw 4 dots
    // Top Left (-1, -1)
    _drawDot(canvas, -1, -1, size.width, expansion, dotRadius, paint);
    // Top Right (1, -1)
    _drawDot(canvas, 1, -1, size.width, expansion, dotRadius, paint);
    // Bottom Left (-1, 1)
    _drawDot(canvas, -1, 1, size.width, expansion, dotRadius, paint);
    // Bottom Right (1, 1)
    _drawDot(canvas, 1, 1, size.width, expansion, dotRadius, paint);

    canvas.restore();
  }

  void _drawDot(Canvas canvas, int dx, int dy, double totalSize, double expansion, double radius, Paint paint) {
    // Original position (inset 0) is essentially touching the center or defined by background position
    // content-box with padding? CSS logic is complex. 
    // Let's approximate: The dots are in corners.
    // CSS: radial-gradient(10.08px at top left, ...) bottom right
    // This implies the dot center is at the corner of the respective quadrant.
    
    // With size 44.8, quadrant is 22.4. Dot radius 10.08.
    // So distinct dots are separated.
    
    // Let's place centers at offset from true center
    final double baseOffset = totalSize / 4; // Center of quadrant
    
    // Expansion moves them OUTWARDS from center
    final double currentOffset = baseOffset + (expansion / 2); // /2 because expansion adds to both sides in CSS inset logic? 
    // CSS Inset: negative value = element grows bigger.
    // Here we want the dots to move apart.
    
    final offset = Offset(dx * currentOffset, dy * currentOffset);
    
    // We need to re-create shader for each dot if we want the radial gradient to be strictly local 
    // but the CSS uses a single complex background or multiple gradients.
    // "radial-gradient(10.08px at bottom right...)"
    // This means the gradient center is at the corner of the component? No.
    
    // Simplification for Flutter: Draw 4 circles using the paint.
    // We need to shift the drawing position.
    
    canvas.drawCircle(offset, radius, paint);
  }

  @override
  bool shouldRepaint(covariant _LoaderPainter oldDelegate) {
    return oldDelegate.progress != progress || oldDelegate.color != color;
  }
}
