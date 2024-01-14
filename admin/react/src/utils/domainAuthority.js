export function generateAuthoritySet(nodes, primaryUrlField, profileUrlField) {
  let authorityMatchSet = new Set()
  for (let node of nodes) {
    if (node?.status && node.status === 'deleted') {
      continue
    }
    const primaryUrl = getNestedProperty(node, primaryUrlField)
    const profileUrl = getNestedProperty(node, profileUrlField)
    if (!primaryUrl || !profileUrl) {
      continue
    }
    if (!authorityMatchSet.has(primaryUrl) && checkAuthority(primaryUrl, profileUrl)) {
      authorityMatchSet.add(primaryUrl)
    }
  }
  return authorityMatchSet
}

function getNestedProperty(obj, path) {
  return path.split('.').reduce((acc, part) => acc && acc[part], obj)
}

export function checkAuthority(originPrimaryUrl, originProfileUrl) {
  // Check the domain name is match or not
  const primaryUrl = new URL(addDefaultScheme(originPrimaryUrl))
  const profileUrl = new URL(addDefaultScheme(originProfileUrl))

  // Only get last two parts which is the domain name
  const primaryDomain = primaryUrl.hostname.split('.').slice(-2).join('.')
  const profileDomain = profileUrl.hostname.split('.').slice(-2).join('.')

  return primaryDomain === profileDomain ? 1 : 0
}

export function addDefaultScheme(url) {
  if (!url.startsWith('http://') && !url.startsWith('https://')) {
    return 'https://' + url
  }
  return url
}
