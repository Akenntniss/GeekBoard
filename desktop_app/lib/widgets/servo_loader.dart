import 'dart:math' as math;
import 'dart:ui' as ui;
import 'package:flutter/material.dart';

class ServoLoader extends StatelessWidget {
  const ServoLoader({super.key});

  @override
  Widget build(BuildContext context) {
    return const SizedBox(
      height: 100,
      child: Row(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          AnimatedLetter(letter: 'S', delay: 0),
          SizedBox(width: 5),
          AnimatedLetter(letter: 'E', delay: 200), // User provided E is stylized
          SizedBox(width: 5),
          AnimatedLetter(letter: 'R', delay: 400),
          SizedBox(width: 5),
          AnimatedLetter(letter: 'V', delay: 600), // User provided V
          SizedBox(width: 5),
          AnimatedLetter(letter: 'O', delay: 800), // User provided O
        ],
      ),
    );
  }
}

class AnimatedLetter extends StatefulWidget {
  final String letter;
  final int delay;

  const AnimatedLetter({super.key, required this.letter, this.delay = 0});

  @override
  State<AnimatedLetter> createState() => _AnimatedLetterState();
}

class _AnimatedLetterState extends State<AnimatedLetter> with SingleTickerProviderStateMixin {
  late AnimationController _controller;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 2),
    )..repeat();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 60, 
      height: 60,
      child: CustomPaint(
        painter: LetterPainter(
          letter: widget.letter,
          progress: _controller,
          color: Colors.blue, // Placeholder, using Shader in painter
        ),
      ),
    );
  }
}

class LetterPainter extends CustomPainter {
  final String letter;
  final Animation<double> progress;
  final Color color;

  LetterPainter({required this.letter, required this.progress, required this.color}) : super(repaint: progress);

  @override
  void paint(Canvas canvas, Size size) {
    final Paint paint = Paint()
      ..style = PaintingStyle.stroke
      ..strokeWidth = 6.0
      ..strokeCap = StrokeCap.round;

    // Dimensions: User SVGs were 100x100. Widget is 60x60. Scale factor 0.6.
    canvas.scale(0.6, 0.6);

    // Gradients based on User CSS
    // Blue to Cyan (#0369a1 -> #67e8f9)
    final shaderBlueCyan = ui.Gradient.linear(
      const Offset(0, 62),
      const Offset(0, 2),
      [const Color(0xFF0369a1), const Color(0xFF67e8f9)],
    );
    
    // Cyan to Dark Blue (#38bdf8 -> #075985)
    final shaderCyanBlue = ui.Gradient.linear(
      const Offset(0, 62),
      const Offset(0, 2),
      [const Color(0xFF38bdf8), const Color(0xFF075985)],
    );

    paint.shader = letter == 'V' ? shaderCyanBlue : shaderBlueCyan;

    Path path = Path();
    
    // Define Paths (Based on 100x100 canvas)
    switch (letter) {
      case 'S':
        // Skeleton S
        path.moveTo(80, 20);
        path.lineTo(30, 20);
        path.quadraticBezierTo(20, 20, 20, 30);
        path.lineTo(20, 45);
        path.quadraticBezierTo(20, 50, 30, 50);
        path.lineTo(70, 50);
        path.quadraticBezierTo(80, 50, 80, 60);
        path.lineTo(80, 75);
        path.quadraticBezierTo(80, 85, 70, 85);
        path.lineTo(20, 85);
        break;

      case 'E':
        // User provided E (Outline path, but treated as stroke here for effect, or skeleton?)
        // The user's E is an OUTLINE. drawing it as a stroke look like a double line E.
        // d="M 20,20 L 80,20 L 80,27 L 27,27 L 27,50 L 70,50 L 70,57 L 25,57 L 25,80 L 80,80 L 80,87 L 20,87 Z"
        path.moveTo(20, 20);
        path.lineTo(80, 20);
        path.lineTo(80, 27);
        path.lineTo(27, 27);
        path.lineTo(27, 50);
        path.lineTo(70, 50);
        path.lineTo(70, 57);
        path.lineTo(25, 57);
        path.lineTo(25, 80);
        path.lineTo(80, 80);
        path.lineTo(80, 87);
        path.lineTo(20, 87);
        path.close();
        break;

      case 'R':
        // Skeleton R
        path.moveTo(30, 85);
        path.lineTo(30, 20);
        path.lineTo(60, 20);
        path.quadraticBezierTo(80, 20, 80, 35);
        path.quadraticBezierTo(80, 50, 60, 50);
        path.lineTo(30, 50);
        path.moveTo(50, 50);
        path.lineTo(75, 85);
        break;

      case 'V':
        // User provided V
        // d="M 20,20 L 50,80 L 80,20"
        path.moveTo(20, 20);
        path.lineTo(50, 80);
        path.lineTo(80, 20);
        break;

      case 'O':
        // User provided O (Spinning)
        // d="M 50,15 A 35,35 0 0 1 85,50 A 35,35 0 0 1 50,85 A 35,35 0 0 1 15,50 A 35,35 0 0 1 50,15 Z"
        // Applying rotation for O
        final center = const Offset(50, 50);
        
        // Custom Spin Animation for O's gradient or path?
        // User CSS: .spin { animation: spinDashArray ..., spin ... }
        // The path itself rotates in CSS.
        
        // We will rotate the CANVAS for O
        break;
    }

    // Draw logic
    if (letter == 'O') {
        // O needs special rotation handling + dash array
        _drawO(canvas, paint, shaderBlueCyan);
    } else {
        _drawDashAnimation(canvas, path, paint);
    }
  }

  void _drawDashAnimation(Canvas canvas, Path path, Paint paint) {
    // Mimic CSS: dash { animation: dashArray 2s ease-in-out infinite, dashOffset 2s linear infinite; }
    // Simulating "drawing" effect
    
    ui.PathMetrics pathMetrics = path.computeMetrics();
    for (ui.PathMetric pathMetric in pathMetrics) {
      double length = pathMetric.length;
      
      // ... (code omitted)

      Path dashPath = _createAnimatedDashPath(pathMetric, progress.value);
      canvas.drawPath(dashPath, paint);
    }
  }
  
  Path _createAnimatedDashPath(ui.PathMetric metric, double progress) {
    double totalLength = metric.length;
    double start = (progress * totalLength * 2) % totalLength;
    double end = (start + totalLength * 0.6) % totalLength;
    
    Path p = Path();
    if (end > start) {
        p.addPath(metric.extractPath(start, end), Offset.zero);
    } else {
        p.addPath(metric.extractPath(start, totalLength), Offset.zero);
        p.addPath(metric.extractPath(0, end), Offset.zero);
    }
    return p;
  }

  void _drawO(Canvas canvas, Paint paint, Shader shader) {
     // ...
     
     Path path = Path();
     path.addOval(Rect.fromCircle(center: const Offset(50, 50), radius: 35));
     
     ui.PathMetrics metrics = path.computeMetrics();
     for (ui.PathMetric metric in metrics) {
         Path dashed = _createAnimatedDashPath(metric, progress.value);
         canvas.drawPath(dashed, paint);
     }
  }

  @override
  bool shouldRepaint(LetterPainter oldDelegate) => true;
}
