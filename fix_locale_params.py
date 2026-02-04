#!/usr/bin/env python3
"""
Fix Laravel Admin Controllers - Add $locale parameter
The route group uses prefix '{locale}/admin' which passes locale as the first parameter
to controller methods. Controllers with edit($id), delete($id), update(Request $request, $id)
need to be updated to include $locale as the first parameter.
"""

import os
import re

BASE_DIR = "/Users/thomas/ClientProjects/telco/core/app/Http/Controllers/Admin"

# Patterns to fix
patterns = [
    # edit($id) -> edit($locale, $id)
    (r'public function edit\(\$id\)', 'public function edit($locale, $id)'),
    
    # delete($id) -> delete($locale, $id) 
    (r'public function delete\(\$id\)', 'public function delete($locale, $id)'),
    
    # update(Request $request, $id) -> update(Request $request, $locale, $id)
    # But actually the {id} comes after locale, so it should be:
    # update($locale, Request $request, $id) OR update($locale, $id) depending on route
    # Let's check the actual route patterns first...
]

# Actually, let's be more careful. The routes look like:
# Route::post('/faq/update/{id}/', ...) 
# So the full URL is: /{locale}/admin/faq/update/{id}
# Parameters passed: $locale, $id
# But for POST routes with Request, the Request is injected, not a route param

# So patterns should be:
# edit($id) -> edit($locale, $id)  
# delete($id) -> delete($locale, $id)
# update(Request $request, $id) -> update(Request $request, $locale, $id) - NO!
# Actually for update, Laravel injects Request, then route params in order
# So it becomes: update(Request $request, $locale, $id)

patterns_to_fix = {
    # Method signature fixes
    r'public function edit\(\$id\)': 'public function edit($locale, $id)',
    r'public function delete\(\$id\)': 'public function delete($locale, $id)',
    r'public function update\(Request \$request, \$id\)': 'public function update(Request $request, $locale, $id)',
    r'public function update\(Request \$request,\$id\)': 'public function update(Request $request, $locale, $id)',  # No space variant
}

# Files to process
files_to_fix = []

for filename in os.listdir(BASE_DIR):
    if filename.endswith('.php'):
        filepath = os.path.join(BASE_DIR, filename)
        files_to_fix.append(filepath)

print(f"Processing {len(files_to_fix)} PHP files...")

changes_made = 0
files_changed = []

for filepath in files_to_fix:
    with open(filepath, 'r') as f:
        content = f.read()
    
    original = content
    
    for pattern, replacement in patterns_to_fix.items():
        if re.search(pattern, content):
            content = re.sub(pattern, replacement, content)
            print(f"  Fixed in {os.path.basename(filepath)}: {pattern}")
            changes_made += 1
    
    if content != original:
        with open(filepath, 'w') as f:
            f.write(content)
        files_changed.append(os.path.basename(filepath))

print(f"\nDone! Made {changes_made} changes in {len(files_changed)} files:")
for f in files_changed:
    print(f"  - {f}")
