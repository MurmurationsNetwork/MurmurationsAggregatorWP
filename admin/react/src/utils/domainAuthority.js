export function generateAuthorityMap(nodes, primaryUrlField, profileUrlField) {
  let authorityMatchMap = new Map()
  for (let node of nodes) {
    if (node?.status && node.status === 'deleted') {
      continue
    }
    const primaryUrl = cleanUrl(getNestedProperty(node, primaryUrlField))
    const profileUrl = cleanUrl(getNestedProperty(node, profileUrlField))
    if (!primaryUrl || !profileUrl) {
      continue
    }
    if (!authorityMatchMap.has(primaryUrl) && checkAuthority(primaryUrl, profileUrl)) {
      authorityMatchMap.set(primaryUrl, (authorityMatchMap.get(primaryUrl) || 0) + 1)
    }
  }
  return authorityMatchMap
}

function getNestedProperty(obj, path) {
  return path.split('.').reduce((acc, part) => acc && acc[part], obj)
}

export function checkAuthority(originPrimaryUrl, originProfileUrl) {
  // Check the domain name is match or not
  const primaryUrl = cleanUrl(originPrimaryUrl)
  const profileUrl = cleanUrl(originProfileUrl)

  return primaryUrl === profileUrl ? 1 : 0
}

export function cleanUrl(url) {
  // If the last character is a slash, remove it
  if (url.endsWith('/')) {
    url = url.slice(0, -1);
  }

  // If the string includes ://, discard this substring and everything to the left of it
  const protocolIndex = url.indexOf('://');
  if (protocolIndex !== -1) {
    url = url.substring(protocolIndex + 3);
  }

  // If the string starts with www., remove those 4 characters
  if (url.startsWith('www.')) {
    url = url.substring(4);
  }

  // Remove .json if it's the last part of the URL
  const parts = url.split('/');
  if (parts[parts.length - 1].endsWith('.json')) {
    parts.pop();
  }

  return parts.join('/');
}
