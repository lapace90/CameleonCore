// find-duplicates.js
// Script pour trouver les classes CSS dupliquées entre fichiers

const fs = require('fs');
const path = require('path');

// Dossier à analyser
const stylesDir = './src/assets/styles';

// Fonction pour extraire les classes CSS d'un fichier
function extractClasses(content) {
  const classes = new Set();
  
  // Regex pour capturer les sélecteurs de classes
  const classRegex = /\.([a-zA-Z][a-zA-Z0-9_-]*)/g;
  let match;
  
  while ((match = classRegex.exec(content)) !== null) {
    classes.add(match[1]);
  }
  
  return classes;
}

// Fonction pour analyser tous les fichiers SCSS
function analyzeFiles() {
  const fileClasses = new Map();
  
  function scanDirectory(dir) {
    const files = fs.readdirSync(dir);
    
    files.forEach(file => {
      const filePath = path.join(dir, file);
      const stat = fs.statSync(filePath);
      
      if (stat.isDirectory()) {
        scanDirectory(filePath);
      } else if (file.endsWith('.scss') || file.endsWith('.css')) {
        const content = fs.readFileSync(filePath, 'utf8');
        const classes = extractClasses(content);
        const relativePath = path.relative(stylesDir, filePath);
        
        fileClasses.set(relativePath, classes);
      }
    });
  }
  
  scanDirectory(stylesDir);
  return fileClasses;
}

// Fonction pour trouver les doublons
function findDuplicates(fileClasses) {
  const classToFiles = new Map();
  
  // Créer un mapping classe -> fichiers qui la contiennent
  for (const [file, classes] of fileClasses) {
    for (const className of classes) {
      if (!classToFiles.has(className)) {
        classToFiles.set(className, []);
      }
      classToFiles.get(className).push(file);
    }
  }
  
  // Filtrer les classes qui apparaissent dans plusieurs fichiers
  const duplicates = new Map();
  for (const [className, files] of classToFiles) {
    if (files.length > 1) {
      duplicates.set(className, files);
    }
  }
  
  return duplicates;
}

// Exécuter l'analyse
console.log('🔍 Analyse des doublons CSS...\n');

const fileClasses = analyzeFiles();
const duplicates = findDuplicates(fileClasses);

if (duplicates.size === 0) {
  console.log('✅ Aucun doublon trouvé !');
} else {
  console.log(`🚨 ${duplicates.size} classes dupliquées trouvées :\n`);
  
  // Trier par nombre de fichiers (les plus problématiques d'abord)
  const sortedDuplicates = [...duplicates.entries()].sort((a, b) => b[1].length - a[1].length);
  
  sortedDuplicates.forEach(([className, files]) => {
    console.log(`📝 .${className}`);
    files.forEach(file => {
      console.log(`   📁 ${file}`);
    });
    console.log('');
  });
  
  // Statistiques
  console.log('\n📊 STATISTIQUES :');
  console.log(`Total de classes dupliquées : ${duplicates.size}`);
  
  const highPriorityDuplicates = sortedDuplicates.filter(([, files]) => files.length >= 3);
  if (highPriorityDuplicates.length > 0) {
    console.log(`⚠️  Classes présentes dans 3+ fichiers : ${highPriorityDuplicates.length}`);
  }
  
  // Recommandations
  console.log('\n💡 RECOMMANDATIONS :');
  console.log('1. Commencez par nettoyer les classes présentes dans 3+ fichiers');
  console.log('2. Gardez une seule définition dans le fichier le plus logique');
  console.log('3. Supprimez les autres occurrences');
}