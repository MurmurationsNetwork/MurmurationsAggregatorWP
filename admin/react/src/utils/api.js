export const getWpMaps = async apiUrl => {
  return await fetch(`${apiUrl}/maps`)
}

export const saveWpMap = async (
  apiUrl,
  mapName,
  tagSlug,
  indexUrl,
  queryUrl
) => {
  const body = {
    name: mapName,
    tag_slug: tagSlug,
    index_url: indexUrl,
    query_url: queryUrl
  }

  return await fetchRequest(`${apiUrl}/maps`, 'POST', body)
}

export const updateWpMap = async (
  apiUrl,
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

export const deleteWpMap = async (apiUrl, map_id) => {
  return await fetchRequest(`${apiUrl}/maps/${map_id}`, 'DELETE')
}

export const saveWpNodes = async (apiUrl, tagSlug, profile) => {
  const body = {
    tag_slug: tagSlug,
    profile: profile
  }

  return await fetchRequest(`${apiUrl}/wp-nodes`, 'POST', body)
}

export const updateWpNodes = async (apiUrl, profile) => {
  const body = {
    profile: profile
  }

  return await fetchRequest(`${apiUrl}/wp-nodes`, 'PUT', body)
}

export const deleteWpNodes = async (apiUrl, profile) => {
  const body = {
    profile: profile
  }

  return await fetchRequest(`${apiUrl}/wp-nodes`, 'DELETE', body)
}

export const compareWithWpNodes = async (
  apiUrl,
  mapId,
  profileData,
  profileUrl
) => {
  const body = {
    map_id: mapId,
    data: profileData,
    profile_url: profileUrl
  }

  return await fetchRequest(`${apiUrl}/nodes-comparison`, 'POST', body)
}

export const saveCustomNodes = async (
  apiUrl,
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

export const updateCustomNodes = async (
  apiUrl,
  mapId,
  profileUrl,
  profileData
) => {
  const body = {
    map_id: mapId,
    profile_url: profileUrl,
    data: profileData
  }

  return await fetchRequest(`${apiUrl}/nodes`, 'PUT', body)
}

export const updateCustomNodesStatus = async (
  apiUrl,
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
