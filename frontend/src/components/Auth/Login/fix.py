import re

with open('LoginWidget.jsx', 'r') as f:
    content = f.read()

# Fix first button className
content = re.sub(
    r'className=\{\}>(\s*)<svg',
    '''className={}>
            <svg''',
    content
)

# Fix second button className  
content = re.sub(
    r'className=\{\}>(\s*)<svg className="w-4 h-4 inline-block mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">(\s*)<path strokeLinecap="round" strokeLinejoin="round" strokeWidth=\{2\} d="M3 5a2 2 0 012-2h3',
    '''className={}>
              <svg className="w-4 h-4 inline-block mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5a2 2 0 012-2h3''',
    content
)

with open('LoginWidget.jsx', 'w') as f:
    f.write(content)

print('Fixed')
