import L from 'leaflet'
import MarkerClusterGroup from '@changey/react-leaflet-markercluster'

import iconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png'
import iconUrl from 'leaflet/dist/images/marker-icon.png'
import shadowUrl from 'leaflet/dist/images/marker-shadow.png'
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

function limitString(inputString, maxLength) {
  if (inputString.length <= maxLength) {
    return inputString
  } else {
    return inputString.substring(0, maxLength) + '...'
  }
}

function MapClient({ profiles, apiUrl, map, isMapLoaded, height }) {
  let defaultCenter = []
  defaultCenter[0] = parseFloat(map.map_center_lat) || 48.864716
  defaultCenter[1] = parseFloat(map.map_center_lon) || 2.349014
  let zoom = parseInt(map.map_scale) || 5

  return (
    <div id="map">
      {isMapLoaded ? (
        <MapContainer
          style={{ height: `${height}vh`, width: '100%' }}
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
                    // show loading while waiting for response
                    let popupInfo = event.target.getPopup()
                    popupInfo.setContent('loading from data source...')

                    // get profile data
                    const response = await markerClicked(profile[2], apiUrl)
                    const responseData = await response.json()
                    if (response.status !== 200) {
                      alert(
                        `Error getting post, please contact the administrator, error: ${responseData}`
                      )
                    }
                    let content = ''
                    if (responseData.title) {
                      content += '<strong>' + responseData.title + '</strong>'
                    }
                    if (responseData.profile_data.description) {
                      content +=
                        '<p>' +
                        limitString(
                          responseData.profile_data.description,
                          100
                        ) +
                        '</p>'
                    }
                    if (responseData.profile_data.primary_url) {
                      content +=
                        "<p>More: <a target='_blank' rel='noreferrer' href='" +
                        responseData.profile_data.primary_url +
                        "'>" +
                        responseData.profile_data.primary_url +
                        '</a></p>'
                    }
                    if (responseData.profile_data.image) {
                      content +=
                        "<img src='" +
                        responseData.profile_data.image +
                        "' alt='profile image' width='25' height='25' onerror='this.style.display = \"none\"' />"
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
      ) : null}
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
