import React from 'react'
import PropTypes from 'prop-types'

function TableRow({ response, isSelected, onSelect, setIsPopupOpen }) {
  const handleSeeUpdates = () => {
    setIsPopupOpen(true)
  }

  return (
    <tr>
      <td>
        <input
          type="checkbox"
          checked={isSelected}
          onChange={() => onSelect(response.id)}
          disabled={response.extra_notes === 'unavailable'}
        />
      </td>
      <td className="text-center">{response.id}</td>
      <td className="text-center">{response.name}</td>
      <td className="text-center">{response.profile_url}</td>
      <td className="text-center">{response.status}</td>
      <td className="text-center">
        {response.extra_notes === 'see updates' ? (
          <button onClick={() => handleSeeUpdates()}>See Updates</button>
        ) : (
          response.extra_notes
        )}
      </td>
    </tr>
  )
}

TableRow.propTypes = {
  response: PropTypes.object.isRequired,
  isSelected: PropTypes.bool.isRequired,
  onSelect: PropTypes.func.isRequired,
  setIsPopupOpen: PropTypes.func.isRequired
}

export default TableRow
