// eslint-disable-next-line no-undef
const wordpressUrl = murmurations_aggregator.wordpress_url
// eslint-disable-next-line no-undef
const wp_nonce = murmurations_aggregator.wp_nonce
const apiUrl = `${wordpressUrl}/wp-json/murmurations-aggregator/v1/api`

// Maps Routes
export const getCustomMaps = async () => {
  return await fetch(`${apiUrl}/maps`)
}

export const getCustomMap = async mapId => {
  return await fetch(`${apiUrl}/maps/${mapId}?_wpnonce=${wp_nonce}`)
}

export const saveCustomMap = async (
  mapName,
  tagSlug,
  indexUrl,
  queryUrl,
  mapCenterLat,
  mapCenterLon,
  mapScale
) => {
  const body = {
    name: mapName,
    tag_slug: tagSlug,
    index_url: indexUrl,
    query_url: queryUrl,
    map_center_lat:
      mapCenterLat !== null && mapCenterLat !== undefined
        ? mapCenterLat
        : 46.603354,
    map_center_lon:
      mapCenterLon !== null && mapCenterLon !== undefined
        ? mapCenterLon
        : 1.888334,
    map_scale: mapScale !== null && mapScale !== undefined ? mapScale : 5
  }

  return await fetchRequest(`${apiUrl}/maps?_wpnonce=${wp_nonce}`, 'POST', body)
}

export const updateCustomMap = async (
  mapId,
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

  return await fetchRequest(
    `${apiUrl}/maps/${mapId}?_wpnonce=${wp_nonce}`,
    'PUT',
    body
  )
}

export const updateCustomMapLastUpdated = async (mapId, lastUpdated) => {
  const body = {
    last_updated: lastUpdated
  }

  return await fetchRequest(
    `${apiUrl}/maps/${mapId}/last-updated?_wpnonce=${wp_nonce}`,
    'PUT',
    body
  )
}

export const deleteCustomMap = async mapId => {
  return await fetchRequest(
    `${apiUrl}/maps/${mapId}?_wpnonce=${wp_nonce}`,
    'DELETE'
  )
}

// WP Nodes Routes
export const saveWpNodes = async profile => {
  return await fetchRequest(
    `${apiUrl}/wp-nodes?_wpnonce=${wp_nonce}`,
    'POST',
    profile
  )
}

export const updateWpNodes = async profile => {
  const postId = profile.data.post_id

  return await fetchRequest(
    `${apiUrl}/wp-nodes/${postId}?_wpnonce=${wp_nonce}`,
    'PUT',
    profile
  )
}

export const deleteWpNodes = async postId => {
  return await fetchRequest(
    `${apiUrl}/wp-nodes/${postId}?_wpnonce=${wp_nonce}`,
    'DELETE'
  )
}

export const restoreWpNodes = async postId => {
  return await fetchRequest(
    `${apiUrl}/wp-nodes/${postId}/restore?_wpnonce=${wp_nonce}`,
    'PUT'
  )
}

// Custom Nodes Routes
export const getCustomNodes = async (mapId, profileUrl) => {
  if (profileUrl === undefined) {
    return await fetch(`${apiUrl}/nodes?map_id=${mapId}&_wpnonce=${wp_nonce}`)
  }

  return await fetch(
    `${apiUrl}/nodes?map_id=${mapId}&profile_url=${profileUrl}&_wpnonce=${wp_nonce}`
  )
}

export const getCustomUnavailableNodes = async mapId => {
  return await fetch(
    `${apiUrl}/nodes?map_id=${mapId}&is_available=false&_wpnonce=${wp_nonce}`
  )
}

export const saveCustomNodes = async profile => {
  return await fetchRequest(
    `${apiUrl}/nodes?_wpnonce=${wp_nonce}`,
    'POST',
    profile
  )
}

export const updateCustomNodes = async profile => {
  const nodeId = profile.data.node_id

  return await fetchRequest(
    `${apiUrl}/nodes/${nodeId}?_wpnonce=${wp_nonce}`,
    'PUT',
    profile
  )
}

export const updateCustomNodesStatus = async profile => {
  const nodeId = profile.data.node_id

  return fetchRequest(
    `${apiUrl}/nodes/${nodeId}/status?_wpnonce=${wp_nonce}`,
    'PUT',
    profile
  )
}

export const deleteCustomNodes = async profile => {
  const nodeId = profile.data.node_id

  return await fetchRequest(
    `${apiUrl}/nodes/${nodeId}?_wpnonce=${wp_nonce}`,
    'DELETE'
  )
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
