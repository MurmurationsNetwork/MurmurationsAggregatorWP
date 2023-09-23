// eslint-disable-next-line no-undef
const wordpressUrl = murmurations_aggregator.wordpress_url
const apiUrl = `${wordpressUrl}/wp-json/murmurations-aggregator/v1/api`

export const getCustomMaps = async () => {
  return await fetch(`${apiUrl}/maps`)
}

export const getCustomMap = async tagSlug => {
  return await fetch(`${apiUrl}/maps/${tagSlug}`)
}

export const saveCustomMap = async (mapName, tagSlug, indexUrl, queryUrl) => {
  const body = {
    name: mapName,
    tag_slug: tagSlug,
    index_url: indexUrl,
    query_url: queryUrl
  }

  return await fetchRequest(`${apiUrl}/maps`, 'POST', body)
}

export const updateCustomMap = async (
  tagSlug,
  mapName,
  mapCenterLat,
  mapCenterLon,
  mapScale
) => {
  const body = {
    name: mapName,
    map_center_lat: mapCenterLat,
    map_center_lon: mapCenterLon,
    map_scale: mapScale
  }

  return await fetchRequest(`${apiUrl}/maps/${tagSlug}`, 'PUT', body)
}

export const updateCustomMapLastUpdated = async (tagSlug, lastUpdated) => {
  const body = {
    last_updated: lastUpdated
  }

  return await fetchRequest(
    `${apiUrl}/maps-last-updated/${tagSlug}`,
    'PUT',
    body
  )
}

export const deleteCustomMap = async map_id => {
  return await fetchRequest(`${apiUrl}/maps/${map_id}`, 'DELETE')
}

export const saveWpNodes = async (tagSlug, profile) => {
  const body = {
    tag_slug: tagSlug,
    profile: profile
  }

  return await fetchRequest(`${apiUrl}/wp-nodes`, 'POST', body)
}

export const updateWpNodes = async profile => {
  const body = {
    profile: profile
  }

  return await fetchRequest(`${apiUrl}/wp-nodes`, 'PUT', body)
}

export const deleteWpNodes = async profile => {
  const body = {
    profile: profile
  }

  return await fetchRequest(`${apiUrl}/wp-nodes`, 'DELETE', body)
}

export const compareWithWpNodes = async (mapId, profileData, profileUrl) => {
  const body = {
    map_id: mapId,
    data: profileData,
    profile_url: profileUrl
  }

  return await fetchRequest(`${apiUrl}/nodes-comparison`, 'POST', body)
}

export const getCustomNodes = async (mapId, profileUrl) => {
  if (profileUrl === undefined) {
    return await fetch(`${apiUrl}/nodes?map_id=${mapId}`)
  }

  return await fetch(
    `${apiUrl}/nodes?map_id=${mapId}&profile_url=${profileUrl}`
  )
}

export const saveCustomNodes = async (
  profileUrl,
  profileData,
  mapId,
  profileStatus
) => {
  const body = {
    profile_url: profileUrl,
    data: profileData,
    map_id: mapId,
    status: profileStatus
  }

  return await fetchRequest(`${apiUrl}/nodes`, 'POST', body)
}

export const updateCustomNodes = async (mapId, profileUrl, profileData) => {
  const body = {
    map_id: mapId,
    profile_url: profileUrl,
    data: profileData
  }

  return await fetchRequest(`${apiUrl}/nodes`, 'PUT', body)
}

export const updateCustomNodesStatus = async (
  mapId,
  profileUrl,
  updateStatus
) => {
  const body = {
    map_id: mapId,
    profile_url: profileUrl,
    status: updateStatus
  }

  return fetchRequest(`${apiUrl}/nodes-status`, 'POST', body)
}

export const deleteCustomNodes = async (mapId, profileUrl) => {
  const body = {
    map_id: mapId,
    profile_url: profileUrl
  }

  return await fetchRequest(`${apiUrl}/nodes`, 'DELETE', body)
}

const fetchRequest = async (url, method, body) => {
  try {
    return await fetch(url, {
      method: method,
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(body)
    })
  } catch (error) {
    alert(`Fetch Request error: ${error}`)
  }
}
