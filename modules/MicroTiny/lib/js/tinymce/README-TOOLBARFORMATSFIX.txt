In relevant theme.js (e.g. silver):

const title = 'Formats';
....
return {
....
        text: Optional.none(),//Optional.some(fallbackFormat),
        icon: Optional.some('format'),//Optional.none(),

PROBABLY ALSO

const title$3 = 'Blocks';
....
return {
....
        text: Optional.none(),//Optional.some(fallbackFormat),
        icon: Optional.some('blocks'),//Optional.none(),

FONTS

'Open Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif

--- tinymce/themes/silver/ORIGINAL-theme.js	2023-12-14 08:23:31.211603093 +1100
+++ tinymce/themes/silver/WORK-theme.js	2023-12-14 17:07:10.829943315 +1100
@@ -23437,6 +23437,6 @@
       return {
         tooltip: getTooltipText(editor, title$3, fallbackFormat),
-        text: Optional.some(fallbackFormat),
-        icon: Optional.none(),
+        text: Optional.none(),
+        icon: Optional.some('blocks'),
         isSelectedFor,
         getCurrentValue: Optional.none,
@@ -23462,8 +23462,11 @@
     const systemFont = 'System Font';
     const systemStackFonts = [
-      '-apple-system',
-      'Segoe UI',
-      'Roboto',
+//    '-apple-system',
+//    'Segoe UI',
+//    'Roboto',
+      'Open Sans',
       'Helvetica Neue',
+      'Helvetica',
+      'Arial',
       'sans-serif'
     ];
@@ -24003,6 +24006,6 @@
       return {
         tooltip: getTooltipText(editor, title, fallbackFormat),
-        text: Optional.some(fallbackFormat),
-        icon: Optional.none(),
+        text: Optional.none(),
+        icon: Optional.some('format'),
         isSelectedFor,
         getCurrentValue: Optional.none,


