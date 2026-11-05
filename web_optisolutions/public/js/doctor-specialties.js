function doctorSpecialties(specialty) {
    return [...new Set(String(specialty || '').split('/').map(value => value.trim()).filter(Boolean))];
}
