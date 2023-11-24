import React from 'react'
import PropTypes from 'prop-types'
import { getCustomNodes } from '../utils/api'

function TableRow({
  response,
  isSelected,
  onSelect,
  setIsPopupOpen,
  setOriginalJson,
  setModifiedJson
}) {
  const handleSeeUpdates = async (mapId, profileUrl, profileData) => {
    const response = await getCustomNodes(mapId, profileUrl)
    const responseData = await response.json()
    if (!response.ok) {
      alert(
        `Error fetching original profile with response: ${
          response.status
        } ${JSON.stringify(responseData)}`
      )
      return
    }

    setOriginalJson(responseData[0].profile_data)
    setModifiedJson(profileData)
    setIsPopupOpen(true)
  }

  return (
    <tr>
      <td>
        <input
          type="checkbox"
          checked={isSelected}
          onChange={() => onSelect(response.id)}
          disabled={!response.data.is_available}
        />
      </td>
      <td className="text-center">{response.id}</td>
      <td className="text-center">{response.profile_data.name}</td>
      <td className="text-center underline">
        <a
          href={response.index_data.profile_url}
          target="_blank"
          rel="noreferrer"
        >
          {response.index_data.profile_url}
        </a>
      </td>
      <td className="text-center">{response.data.status}</td>
      <td className="text-center">
        {response.data.is_available
          ? 'Available'
          : 'Unavailable-' +
            (response.data.unavailable_message
              ? response.data.unavailable_message
              : '')}
      </td>
      <td className="text-center">
        {response.data.extra_notes === 'see updates' ? (
          <button
            onClick={() =>
              handleSeeUpdates(
                response.data.map_id,
                response.index_data.profile_url,
                response.profile_data
              )
            }
          >
            See Updates
          </button>
        ) : (
          response.data.extra_notes
        )}
      </td>
      <td className="text-center">
        {response?.profile_data?.geolocation ? '📍' : ''}
      </td>
    </tr>
  )
}

TableRow.propTypes = {
  response: PropTypes.object.isRequired,
  isSelected: PropTypes.bool.isRequired,
  onSelect: PropTypes.func.isRequired,
  setIsPopupOpen: PropTypes.func.isRequired,
  setOriginalJson: PropTypes.func.isRequired,
  setModifiedJson: PropTypes.func.isRequired
}

export default TableRow
