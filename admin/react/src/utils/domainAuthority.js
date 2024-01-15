export function generateAuthorityMap(nodes, primaryUrlField, profileUrlField) {
  let authorityMatchMap = new Map()
  for (let node of nodes) {
    if (node?.status && node.status === 'deleted') {
      continue
    }
    const primaryUrl = addDefaultScheme(getNestedProperty(node, primaryUrlField))
    const profileUrl = addDefaultScheme(getNestedProperty(node, profileUrlField))
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
  try {
    const primaryUrl = new URL(originPrimaryUrl)
    const profileUrl = new URL(originProfileUrl)

    if (!primaryUrl.protocol.startsWith('http') ||
      !profileUrl.protocol.startsWith('http')) {
      console.log('Invalid protocol')
      return 0
    }

    const primaryDomain = checkTrailingSlash(primaryUrl.hostname + primaryUrl.pathname)
    const profileDomain = profileUrl.hostname + profileUrl.pathname.split('/').slice(0,-1).join('/')

    return primaryDomain === profileDomain ? 1 : 0

  }
  catch (e) {
    console.log(e)
    return 0
  }
}

export function addDefaultScheme(url) {
  if (!url.startsWith('http://') && !url.startsWith('https://')) {
    return 'https://' + url
  }
  return url
}

function checkTrailingSlash(url) {
  if(url.endsWith('/')){
    return url.slice(0,-1)
  }
  return url
}