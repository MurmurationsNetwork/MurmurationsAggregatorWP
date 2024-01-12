import {whiteList} from "../data/whiteList";

export function generateUrlMap(nodes) {
  let primaryUrlCount = new Map()
  for (let node of nodes) {
    if (node?.profile_data?.primary_url) {
      const cleanUrl = node.profile_data.primary_url.replace(
        /^https?:\/\//,
        ''
      )
      primaryUrlCount.set(
        cleanUrl,
        (primaryUrlCount.get(cleanUrl) || 0) + 1
      )
    }
  }
  return primaryUrlCount
}

export function checkAuthority(originPrimaryUrl, originProfileUrl) {
  // check the domain name is match or not
  const primaryUrl = new URL(
    addDefaultScheme(originPrimaryUrl)
  )
  const profileUrl = new URL(
    addDefaultScheme(originProfileUrl)
  )

  // only get last two parts which is the domain name
  const primaryDomain = primaryUrl.hostname
    .split('.')
    .slice(-2)
    .join('.')
  const profileDomain = profileUrl.hostname
    .split('.')
    .slice(-2)
    .join('.')

  return !(primaryDomain !== profileDomain &&
    !whiteList.includes(profileDomain));
}

function addDefaultScheme(url) {
  if (
    !url.startsWith('http://') &&
    !url.startsWith('https://')
  ) {
    return 'https://' + url
  }
  return url
}