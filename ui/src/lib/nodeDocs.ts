const nodeDocsRaw = import.meta.glob(
  '../../../docs/nodes/*.md',
  { query: '?raw', eager: true }
) as Record<string, { default: string }>

const triggerDocsRaw = import.meta.glob(
  '../../../docs/triggers/*.md',
  { query: '?raw', eager: true }
) as Record<string, { default: string }>

function buildMap(rawMap: Record<string, { default: string }>): Record<string, string> {
  const result: Record<string, string> = {}
  for (const [path, mod] of Object.entries(rawMap)) {
    const filename = path.split('/').pop()?.replace(/\.md$/, '') ?? ''
    const nodeKey = filename.replace(/-/g, '_')
    result[nodeKey] = mod.default
  }
  return result
}

const nodeDocs = buildMap(nodeDocsRaw)
const triggerDocs = buildMap(triggerDocsRaw)

export function getNodeDoc(nodeKey: string, nodeType: string): string | null {
  if (nodeType === 'trigger') {
    return triggerDocs[nodeKey] ?? null
  }
  return nodeDocs[nodeKey] ?? null
}
