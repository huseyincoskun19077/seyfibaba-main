import glob, os

pages = glob.glob('/var/www/shopo/frontend/src/app/(website)/*/page.js')
for p in pages:
    if any(x in p for x in ['login', 'forgot-password', 'about', 'contact']):
        with open(p, 'r+') as f:
            c = f.read()
            if 'force-dynamic' not in c:
                f.seek(0)
                f.write('export const dynamic = "force-dynamic";\n' + c)
                print('Fixed:', p)
            else:
                print('Has dynamic:', p)

# Also add timeout to next.config
with open('/var/www/shopo/frontend/next.config.mjs', 'r') as f:
    c = f.read()
if 'staticPageGenerationTimeout' not in c:
    c = c.replace('  compress: true,', '  staticPageGenerationTimeout: 180,\n  compress: true,', 1)
    with open('/var/www/shopo/frontend/next.config.mjs', 'w') as f:
        f.write(c)
    print('Added staticPageGenerationTimeout to next.config.mjs')