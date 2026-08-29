/**
 * Türkçe karakter destekli slug üretici.
 * Tüm admin/seller blade dosyalarında kullanılır.
 */
function convertToSlug(text) {
    var trMap = {
        'ç': 'c', 'Ç': 'c',
        'ğ': 'g', 'Ğ': 'g',
        'ı': 'i', 'I': 'i',
        'İ': 'i',
        'ö': 'o', 'Ö': 'o',
        'ş': 's', 'Ş': 's',
        'ü': 'u', 'Ü': 'u',
        'â': 'a', 'Â': 'a',
        'î': 'i', 'Î': 'i',
        'û': 'u', 'Û': 'u'
    };
    var str = text.toLowerCase();
    var result = '';
    for (var i = 0; i < str.length; i++) {
        result += trMap[str[i]] || str[i];
    }
    return result
        .replace(/[^a-z0-9 -]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
}
