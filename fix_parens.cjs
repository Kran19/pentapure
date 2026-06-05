const fs = require('fs');

function fix(filepath) {
  if (!fs.existsSync(filepath)) return;
  let content = fs.readFileSync(filepath, 'utf8');
  
  content = content.replace(/\$\{s\.name\}\(\)<\/option>/g, '${s.name}(${s.grade})</option>');
  content = content.replace(/<div class="list-item-meta">\(\)<\/div>/g, '<div class="list-item-meta">(${s.grade})</div>');
  content = content.replace(/<div style="font-size:0\.8rem; color:var\(--text-muted\); margin-top:4px;">\(\)<\/div>/g, '<div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">(${s.grade})</div>');
  content = content.replace(/<div style="font-size:0\.85rem; margin-top:2px;">\(\)<\/div>/g, '<div style="font-size:0.85rem; margin-top:2px;">(${outGrade})</div>');
  content = content.replace(/<div class="list-item-meta">\s*\(\)\s*<\/div>/g, '<div class="list-item-meta">\n              (${s.grade})\n            </div>');
  
  fs.writeFileSync(filepath, content, 'utf8');
  console.log('Fixed ' + filepath);
}

fix('public/js/app.js');
fix('prototype_pentapure/js/app.js');
