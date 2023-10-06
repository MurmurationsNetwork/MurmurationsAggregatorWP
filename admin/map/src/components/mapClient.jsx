import L from 'leaflet'
import MarkerClusterGroup from '@changey/react-leaflet-markercluster'

import iconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png'
import iconUrl from 'leaflet/dist/images/marker-icon.png'
import shadowUrl from 'leaflet/dist/images/marker-shadow.png'
import { useEffect } from 'react'
import PropTypes from 'prop-types'
import { MapContainer, Marker, Popup, TileLayer } from 'react-leaflet'

import 'leaflet/dist/leaflet.css'
import '@changey/react-leaflet-markercluster/dist/styles.min.css'

delete L.Icon.Default.prototype._getIconUrl

L.Icon.Default.mergeOptions({
  iconUrl: iconUrl,
  iconRetinaUrl: iconRetinaUrl,
  shadowUrl: shadowUrl
})

const markerClicked = async (postId, apiUrl) => {
  try {
    return await fetch(`${apiUrl}/api/wp-nodes/${postId}`)
  } catch (error) {
    alert(
      `Error getting post, please contact the administrator, error: ${error}`
    )
  }
}

function MapClient({ profiles, apiUrl, map, isMapLoaded, height }) {
  let defaultCenter = []
  defaultCenter[0] = parseFloat(map.map_center_lat) || 48.864716
  defaultCenter[1] = parseFloat(map.map_center_lon) || 2.349014
  let zoom = parseInt(map.map_scale) || 5

  useEffect(() => {
    (async function init() {
      delete L.Icon.Default.prototype._getIconUrl
    })()
  }, [])

  return (
    <div id="map">
      { isMapLoaded ? (
        <MapContainer
          style={{ height: `${height}px`, width: '100%' }}
          center={defaultCenter}
          zoom={zoom}
        >
          <TileLayer
            url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
            attribution='&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
          />
          <MarkerClusterGroup>
            {profiles.map(profile => (
              <Marker
                key={profile[2]}
                position={[profile[1], profile[0]]}
                eventHandlers={{
                  click: async event => {
                    const response = await markerClicked(profile[2], apiUrl)
                    const responseData = await response.json()
                    if (response.status !== 200) {
                      alert(
                        `Error getting post, please contact the administrator, error: ${responseData}`
                      )
                    }
                    let popupInfo = event.target.getPopup()
                    let content = ''
                    if (responseData.title) {
                      content +=
                        '<strong>Title: ' + responseData.title + '</strong>'
                    }
                    if (responseData.description) {
                      content +=
                        '<p>Description: ' + responseData.description + '</p>'
                    }
                    if (responseData.post_url) {
                      content +=
                        "<p>URL: <a target='_blank' rel='noreferrer' href='" +
                        responseData.post_url +
                        "'>" +
                        responseData.post_url +
                        '</a></p>'
                    }
                    popupInfo.setContent(content)
                  }
                }}
              >
                <Popup></Popup>
              </Marker>
            ))}
          </MarkerClusterGroup>
        </MapContainer>
      ) : null
      }
    </div>
  )
}

MapClient.propTypes = {
  profiles: PropTypes.array.isRequired,
  apiUrl: PropTypes.string.isRequired,
  map: PropTypes.object.isRequired,
  isMapLoaded: PropTypes.bool.isRequired,
  height: PropTypes.number.isRequired
}

export default MapClient
