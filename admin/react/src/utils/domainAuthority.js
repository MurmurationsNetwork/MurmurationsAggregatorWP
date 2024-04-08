import { getPrimaryUrlMap } from './api'

export async function getAuthorityMap(mapId) {
  try {
    const response = await getPrimaryUrlMap(mapId)
    if (response.status === 404) {
      return []
    }

    const responseData = await response.json()
    if (!response.ok) {
      throw new Error(
        `Error fetching authority map with response: ${
          response.status
        } ${JSON.stringify(responseData)}`
      )
    }

    return responseData
  } catch (error) {
    throw new Error(
      `Error getting authority maps, please contact the administrator, error: ${error}`
    )
  }
}

export function checkAuthority(
  authorityMap,
  originPrimaryUrl,
  originProfileUrl
) {
  try {
    if (originPrimaryUrl === 'https://') {
      console.log('Invalid URL')
      return 1
    }

    const primaryUrl = new URL(addDefaultScheme(originPrimaryUrl))
    const profileUrl = new URL(addDefaultScheme(originProfileUrl))

    if (
      !primaryUrl.protocol.startsWith('http') ||
      !profileUrl.protocol.startsWith('http')
    ) {
      console.log('Invalid protocol')
      return 1
    }

    // If the primary URL is in the authority map, it means that the primary URL has other profiles that have authority, so if the profile URL is not the same as the primary URL, it does not have authority
    if (
      authorityMap[primaryUrl.hostname] !== undefined &&
      primaryUrl.hostname !== profileUrl.hostname
    ) {
      return 0
    }

    return 1
  } catch (e) {
    console.log(e)
    return 1
  }
}

export function addDefaultScheme(url) {
  if (
    url !== undefined &&
    !url?.startsWith('http://') &&
    !url?.startsWith('https://')
  ) {
    return 'https://' + url
  }
  return url
}
