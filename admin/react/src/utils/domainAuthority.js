export function generateUrlMap(nodes, urlField) {
  let primaryUrlCount = new Map()
  for (let node of nodes) {
    if (node?.status && node.status === 'deleted') {
      continue
    }
    let url = getNestedProperty(node, urlField)
    if (url) {
      const cleanUrl = url.replace(/^https?:\/\//, '')
      primaryUrlCount.set(cleanUrl, (primaryUrlCount.get(cleanUrl) || 0) + 1)
    }
  }
  return primaryUrlCount
}

function getNestedProperty(obj, path) {
  return path.split('.').reduce((acc, part) => acc && acc[part], obj)
}

export function checkAuthority(originPrimaryUrl, originProfileUrl) {
  // check the domain name is match or not
  const primaryUrl = new URL(addDefaultScheme(originPrimaryUrl))
  const profileUrl = new URL(addDefaultScheme(originProfileUrl))

  // only get last two parts which is the domain name
  const primaryDomain = primaryUrl.hostname.split('.').slice(-2).join('.')
  const profileDomain = profileUrl.hostname.split('.').slice(-2).join('.')

  return !(
    primaryDomain !== profileDomain
  )
}

function addDefaultScheme(url) {
  if (!url.startsWith('http://') && !url.startsWith('https://')) {
    return 'https://' + url
  }
  return url
}
