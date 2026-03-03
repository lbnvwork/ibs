export function extractData(response) {
    const data = response.data
    if (data.member) {
        return {
            items: data.member,
            totalItems: data.totalItems,
            view: data.view,
            next: data.view?.next || null
        }
    }
    return data
}

export function extractIdFromIri(iri) {
    if (!iri) return null
    const match = iri.match(/\/(\d+)$/)
    return match ? parseInt(match[1], 10) : null
}

export function createPaginationParams(page = 1, itemsPerPage = 30, filters = {}) {
    return { page, itemsPerPage, ...filters }
}