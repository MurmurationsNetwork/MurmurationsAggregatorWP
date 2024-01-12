import { getProxyData } from './api'

export async function fetchProfileData(profileUrl) {
  let profileData = ''
  let fetchProfileError = null

  if (profileUrl) {
    try {
      const response = await getProxyData(profileUrl)
      if (response.ok) {
        profileData = await response.json()
      } else {
        fetchProfileError = 'STATUS-' + response.status
      }
    } catch (error) {
      if (error.message === 'Failed to fetch') {
        fetchProfileError = 'CORS'
      } else {
        fetchProfileError = 'UNKNOWN'
      }
    }
  }

  return { profileData, fetchProfileError }
}
